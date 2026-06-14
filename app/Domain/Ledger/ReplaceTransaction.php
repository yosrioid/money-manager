<?php

namespace App\Domain\Ledger;

use App\Domain\Audit\RecordAuditLog;
use App\Enums\AuditAction;
use App\Enums\CategoryType;
use App\Enums\LedgerEntryType;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use LogicException;

class ReplaceTransaction
{
    public function __construct(
        private readonly LockAccountsForPosting $lockAccounts,
        private readonly RecordAuditLog $recordAuditLog,
    ) {}

    /**
     * @param  array<int, array{account_id: ?int, category_id?: ?int, type: LedgerEntryType, amount: int}>  $entries
     */
    public function replace(Transaction $transaction, array $entries, User $actor, ?string $description = null): Transaction
    {
        return DB::transaction(function () use ($transaction, $entries, $actor, $description): Transaction {
            $transaction = Transaction::query()->whereKey($transaction->id)->lockForUpdate()->firstOrFail();

            if (! $transaction->workspace->memberships()->where('user_id', $actor->id)->exists()) {
                throw new AuthorizationException;
            }

            if ($transaction->getRawOriginal('status') !== TransactionStatus::Posted->value) {
                throw new LogicException('Only posted transactions can be replaced.');
            }

            if (count($entries) < 2) {
                throw new LogicException('Replacement transactions require at least two entries.');
            }

            if (array_sum(array_column($entries, 'amount')) !== 0) {
                throw new LogicException('Replacement transactions must balance to zero.');
            }

            foreach ($entries as $entry) {
                $accountId = $entry['account_id'];
                $categoryId = $entry['category_id'] ?? null;
                $hasValidReference = match ($entry['type']) {
                    LedgerEntryType::Account => $accountId !== null && $categoryId === null,
                    LedgerEntryType::Category => $accountId === null && $categoryId !== null,
                    LedgerEntryType::OpeningBalanceEquity => $accountId === null && $categoryId === null,
                };

                if (! $hasValidReference) {
                    throw new LogicException('Replacement entry references must match their entry type.');
                }
            }

            $accountIds = array_values(array_unique(array_filter([
                ...$transaction->entries()->pluck('account_id')->all(),
                ...array_column($entries, 'account_id'),
            ])));
            $lockedAccounts = $this->lockAccounts->lock($accountIds);

            if ($lockedAccounts->count() !== count($accountIds) || $lockedAccounts->contains(fn (Account $account): bool => $account->workspace_id !== $transaction->workspace_id || $account->currency_code !== $transaction->currency_code)) {
                throw new LogicException('Replacement accounts must belong to the transaction workspace and currency.');
            }

            $categoryIds = array_values(array_unique(array_filter(array_column($entries, 'category_id'))));
            $categories = Category::query()->whereIn('id', $categoryIds)->get();
            $expectedCategoryType = match ($transaction->getRawOriginal('type')) {
                TransactionType::Income->value => CategoryType::Income->value,
                TransactionType::Expense->value, TransactionType::Transfer->value => CategoryType::Expense->value,
                default => null,
            };

            if ($categories->count() !== count($categoryIds) || $categories->contains(fn (Category $category): bool => $category->workspace_id !== $transaction->workspace_id || ($expectedCategoryType !== null && $category->getRawOriginal('type') !== $expectedCategoryType))) {
                throw new LogicException('Replacement categories must belong to the transaction workspace.');
            }

            $postedAt = now();
            $replacement = Transaction::query()->create([
                'workspace_id' => $transaction->workspace_id,
                'created_by' => $actor->id,
                'type' => $transaction->type,
                'status' => TransactionStatus::Draft,
                'currency_code' => $transaction->currency_code,
                'description' => $description ?? "Replacement of: {$transaction->description}",
                'occurred_at' => $postedAt,
                'posted_at' => null,
                'replaces_transaction_id' => $transaction->id,
            ]);

            $replacement->entries()->createMany(array_map(fn (array $entry): array => [
                'workspace_id' => $transaction->workspace_id,
                'account_id' => $entry['account_id'],
                'category_id' => $entry['category_id'] ?? null,
                'type' => $entry['type'],
                'currency_code' => $transaction->currency_code,
                'amount' => $entry['amount'],
                'base_amount' => $entry['amount'],
            ], $entries));

            $replacement->update(['status' => TransactionStatus::Posted, 'posted_at' => $postedAt]);
            $transaction->update(['status' => TransactionStatus::Replaced]);

            $this->recordAuditLog->record($transaction->workspace, AuditAction::TransactionReplaced, $transaction, $actor, [
                'replacement_transaction_id' => $replacement->id,
            ]);

            return $replacement;
        });
    }
}
