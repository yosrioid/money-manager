<?php

namespace App\Domain\Ledger;

use App\Domain\Audit\RecordAuditLog;
use App\Enums\AuditAction;
use App\Enums\LedgerEntryType;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Workspace;
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
     * @param  array<int, array{account_id: ?int, category_id: ?int, type: LedgerEntryType, amount: int}>  $entries
     */
    public function post(Workspace $workspace, TransactionType $type, string $currencyCode, string $description, CarbonInterface $occurredAt, array $entries, User $actor): Transaction
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

        if (array_sum(array_column($entries, 'amount')) !== 0) {
            throw new LogicException('Posted transactions must balance to zero.');
        }

        return DB::transaction(function () use ($workspace, $type, $currencyCode, $description, $occurredAt, $entries, $actor): Transaction {
            $accountIds = array_values(array_unique(array_filter(array_column($entries, 'account_id'))));
            $lockedAccounts = $this->lockAccounts->lock($accountIds);
            $categoryIds = array_values(array_unique(array_filter(array_column($entries, 'category_id'))));
            $categories = Category::query()->whereIn('id', $categoryIds)->get();

            if ($lockedAccounts->count() !== count($accountIds) || $lockedAccounts->contains(fn (Account $account): bool => $account->workspace_id !== $workspace->id || $account->currency_code !== $currencyCode)) {
                throw new LogicException('Posted accounts must belong to the transaction workspace and currency.');
            }

            if ($categories->count() !== count($categoryIds) || $categories->contains(fn (Category $category): bool => $category->workspace_id !== $workspace->id)) {
                throw new LogicException('Posted categories must belong to the transaction workspace.');
            }

            $postedAt = now();
            $transaction = Transaction::query()->create([
                'workspace_id' => $workspace->id,
                'created_by' => $actor->id,
                'type' => $type,
                'status' => TransactionStatus::Draft,
                'currency_code' => $currencyCode,
                'description' => $description,
                'occurred_at' => $occurredAt,
                'posted_at' => null,
            ]);

            $transaction->entries()->createMany(array_map(fn (array $entry): array => [
                'workspace_id' => $workspace->id,
                'account_id' => $entry['account_id'],
                'category_id' => $entry['category_id'],
                'type' => $entry['type'],
                'currency_code' => $currencyCode,
                'amount' => $entry['amount'],
            ], $entries));

            $transaction->update(['status' => TransactionStatus::Posted, 'posted_at' => $postedAt]);
            $this->recordAuditLog->record($workspace, AuditAction::TransactionPosted, $transaction, $actor);

            return $transaction;
        });
    }
}
