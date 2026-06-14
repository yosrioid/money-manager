<?php

namespace App\Domain\Ledger;

use App\Domain\Audit\RecordAuditLog;
use App\Enums\AuditAction;
use App\Enums\LedgerEntryType;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Merchant;
use App\Models\Tag;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Workspace;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use LogicException;

class PostTransaction
{
    public function __construct(
        private readonly LockAccountsForPosting $lockAccounts,
        private readonly RecordAuditLog $recordAuditLog,
    ) {}

    /**
     * @param  array<int, array{account_id: ?int, category_id: ?int, type: LedgerEntryType, amount: int, currency_code?: string, base_amount?: int}>  $entries
     * @param  array<int, Tag>  $tags
     */
    public function post(Workspace $workspace, TransactionType $type, string $currencyCode, string $description, CarbonInterface $occurredAt, array $entries, User $actor, ?Merchant $merchant = null, ?string $memo = null, array $tags = [], ?string $idempotencyKey = null, ?string $exchangeRate = null): Transaction
    {
        if (! $workspace->memberships()->where('user_id', $actor->id)->exists()) {
            throw new AuthorizationException;
        }

        if (count($entries) < 2) {
            throw new LogicException('Posted transactions require at least two entries.');
        }

        foreach ($entries as $entry) {
            $hasValidReference = match ($entry['type']) {
                LedgerEntryType::Account => $entry['account_id'] !== null && $entry['category_id'] === null,
                LedgerEntryType::Category => $entry['account_id'] === null && $entry['category_id'] !== null,
                LedgerEntryType::OpeningBalanceEquity => $entry['account_id'] === null && $entry['category_id'] === null,
            };

            if (! $hasValidReference) {
                throw new LogicException('Ledger entry references must match their entry type.');
            }
        }

        $baseAmounts = array_map(fn (array $entry): int => $entry['base_amount'] ?? $entry['amount'], $entries);

        if (array_sum($baseAmounts) !== 0) {
            throw new LogicException('Posted transactions must balance to zero in their base currency.');
        }

        if ($merchant instanceof Merchant && ($merchant->workspace_id !== $workspace->id || $merchant->archived_at !== null)) {
            throw new LogicException('The merchant must be active and belong to the transaction workspace.');
        }

        if ($merchant instanceof Merchant && ! in_array($type, [TransactionType::Income, TransactionType::Expense], true)) {
            throw new LogicException('Only income and expense transactions can reference a merchant or recipient.');
        }

        if (collect($tags)->contains(fn (Tag $tag): bool => $tag->workspace_id !== $workspace->id || $tag->archived_at !== null)) {
            throw new LogicException('Transaction tags must be active and belong to the transaction workspace.');
        }

        return DB::transaction(function () use ($workspace, $type, $currencyCode, $description, $occurredAt, $entries, $actor, $merchant, $memo, $tags, $idempotencyKey, $exchangeRate): Transaction {
            if ($idempotencyKey !== null) {
                $existingTransaction = Transaction::query()
                    ->where('workspace_id', $workspace->id)
                    ->where('idempotency_key', $idempotencyKey)
                    ->lockForUpdate()
                    ->first();

                if ($existingTransaction instanceof Transaction) {
                    $this->assertMatchingIdempotentRequest($existingTransaction, $type, $currencyCode, $description, $occurredAt, $entries, $merchant, $memo, $tags);

                    return $existingTransaction;
                }
            }

            $accountIds = array_values(array_unique(array_filter(array_column($entries, 'account_id'))));
            $lockedAccounts = $this->lockAccounts->lock($accountIds);
            $categoryIds = array_values(array_unique(array_filter(array_column($entries, 'category_id'))));
            $categories = Category::query()->whereIn('id', $categoryIds)->get();

            $entryCurrencyByAccountId = [];

            foreach ($entries as $entry) {
                if ($entry['type'] === LedgerEntryType::Account && $entry['account_id'] !== null) {
                    $entryCurrencyByAccountId[$entry['account_id']] = $entry['currency_code'] ?? $currencyCode;
                }
            }

            if ($lockedAccounts->count() !== count($accountIds) || $lockedAccounts->contains(fn (Account $account): bool => $account->workspace_id !== $workspace->id || $account->currency_code !== ($entryCurrencyByAccountId[$account->id] ?? $currencyCode) || $account->archived_at !== null)) {
                throw new LogicException('Posted accounts must be active and belong to the transaction workspace and currency.');
            }

            if ($categories->count() !== count($categoryIds) || $categories->contains(fn (Category $category): bool => $category->workspace_id !== $workspace->id || $category->archived_at !== null)) {
                throw new LogicException('Posted categories must be active and belong to the transaction workspace.');
            }

            $postedAt = now();
            $transaction = Transaction::query()->create([
                'workspace_id' => $workspace->id,
                'created_by' => $actor->id,
                'idempotency_key' => $idempotencyKey,
                'merchant_id' => $merchant?->id,
                'type' => $type,
                'status' => TransactionStatus::Draft,
                'currency_code' => $currencyCode,
                'exchange_rate' => $exchangeRate,
                'description' => $description,
                'memo' => $memo,
                'occurred_at' => $occurredAt,
                'posted_at' => null,
            ]);

            $transaction->entries()->createMany(array_map(fn (array $entry): array => [
                'workspace_id' => $workspace->id,
                'account_id' => $entry['account_id'],
                'category_id' => $entry['category_id'],
                'type' => $entry['type'],
                'currency_code' => $entry['currency_code'] ?? $currencyCode,
                'amount' => $entry['amount'],
                'base_amount' => $entry['base_amount'] ?? $entry['amount'],
            ], $entries));

            $transaction->tags()->attach(collect($tags)->mapWithKeys(fn (Tag $tag): array => [
                $tag->id => ['workspace_id' => $workspace->id],
            ]));

            $transaction->update(['status' => TransactionStatus::Posted, 'posted_at' => $postedAt]);
            $this->recordAuditLog->record($workspace, AuditAction::TransactionPosted, $transaction, $actor);

            return $transaction;
        });
    }

    /**
     * @param  array<int, array{account_id: ?int, category_id: ?int, type: LedgerEntryType, amount: int, currency_code?: string, base_amount?: int}>  $entries
     * @param  array<int, Tag>  $tags
     */
    private function assertMatchingIdempotentRequest(Transaction $transaction, TransactionType $type, string $currencyCode, string $description, CarbonInterface $occurredAt, array $entries, ?Merchant $merchant, ?string $memo, array $tags): void
    {
        $transaction->loadMissing('entries', 'tags');

        $existingEntries = $transaction->entries
            ->map(fn ($entry): array => [
                'account_id' => $entry->account_id,
                'category_id' => $entry->category_id,
                'type' => $entry->getRawOriginal('type'),
                'amount' => $entry->amount,
                'currency_code' => $entry->currency_code,
                'base_amount' => $entry->base_amount,
            ])
            ->sort()
            ->values()
            ->all();
        $requestedEntries = collect($entries)
            ->map(fn (array $entry): array => [
                'account_id' => $entry['account_id'],
                'category_id' => $entry['category_id'],
                'type' => $entry['type']->value,
                'amount' => $entry['amount'],
                'currency_code' => $entry['currency_code'] ?? $currencyCode,
                'base_amount' => $entry['base_amount'] ?? $entry['amount'],
            ])
            ->sort()
            ->values()
            ->all();
        $existingTagIds = $transaction->tags->pluck('id')->sort()->values()->all();
        $requestedTagIds = collect($tags)->pluck('id')->sort()->values()->all();

        $matches = $transaction->getRawOriginal('type') === $type->value
            && $transaction->currency_code === $currencyCode
            && $transaction->description === $description
            && CarbonImmutable::parse($transaction->getRawOriginal('occurred_at'))->equalTo($occurredAt)
            && $transaction->merchant_id === $merchant?->id
            && $transaction->memo === $memo
            && $existingEntries === $requestedEntries
            && $existingTagIds === $requestedTagIds;

        if (! $matches) {
            throw new LogicException('The idempotency key has already been used for a different transaction.');
        }
    }
}
