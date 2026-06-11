<?php

namespace App\Domain\Ledger;

use App\Domain\Audit\RecordAuditLog;
use App\Enums\AuditAction;
use App\Enums\LedgerEntryType;
use App\Enums\TransactionStatus;
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
     * @param  array<int, array{account_id: ?int, type: LedgerEntryType, amount: int}>  $entries
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

            if (array_sum(array_column($entries, 'amount')) !== 0) {
                throw new LogicException('Replacement transactions must balance to zero.');
            }

            $this->lockAccounts->lock([
                ...$transaction->entries()->pluck('account_id')->all(),
                ...array_column($entries, 'account_id'),
            ]);

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
                'type' => $entry['type'],
                'currency_code' => $transaction->currency_code,
                'amount' => $entry['amount'],
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
