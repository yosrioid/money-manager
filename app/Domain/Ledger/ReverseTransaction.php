<?php

namespace App\Domain\Ledger;

use App\Domain\Audit\RecordAuditLog;
use App\Enums\AuditAction;
use App\Enums\TransactionStatus;
use App\Models\Transaction;
use App\Models\TransactionEntry;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use LogicException;

class ReverseTransaction
{
    public function __construct(
        private readonly LockAccountsForPosting $lockAccounts,
        private readonly RecordAuditLog $recordAuditLog,
    ) {}

    public function reverse(Transaction $transaction, User $actor): Transaction
    {
        return DB::transaction(function () use ($transaction, $actor): Transaction {
            $transaction = Transaction::query()->whereKey($transaction->id)->lockForUpdate()->firstOrFail();

            if (! $transaction->workspace->memberships()->where('user_id', $actor->id)->exists()) {
                throw new AuthorizationException;
            }

            if ($transaction->getRawOriginal('status') !== TransactionStatus::Posted->value) {
                throw new LogicException('Only posted transactions can be reversed.');
            }

            $this->lockAccounts->lock($transaction->entries()->pluck('account_id')->all());

            $postedAt = now();
            $reversal = Transaction::query()->create([
                'workspace_id' => $transaction->workspace_id,
                'created_by' => $actor->id,
                'type' => $transaction->type,
                'status' => TransactionStatus::Draft,
                'currency_code' => $transaction->currency_code,
                'exchange_rate' => $transaction->getRawOriginal('exchange_rate'),
                'description' => "Reversal of: {$transaction->description}",
                'occurred_at' => $postedAt,
                'posted_at' => null,
                'reverses_transaction_id' => $transaction->id,
            ]);

            $reversal->entries()->createMany(
                $transaction->entries->map(fn (TransactionEntry $entry): array => [
                    'workspace_id' => $entry->workspace_id,
                    'account_id' => $entry->account_id,
                    'category_id' => $entry->category_id,
                    'type' => $entry->type,
                    'currency_code' => $entry->currency_code,
                    'amount' => -$entry->amount,
                    'base_amount' => -$entry->base_amount,
                ])->all()
            );

            if ($reversal->entries()->sum('base_amount') !== 0) {
                throw new LogicException('Reversal transactions must balance to zero.');
            }

            $reversal->update(['status' => TransactionStatus::Posted, 'posted_at' => $postedAt]);
            $transaction->update(['status' => TransactionStatus::Reversed]);

            $this->recordAuditLog->record($transaction->workspace, AuditAction::TransactionReversed, $transaction, $actor, [
                'reversal_transaction_id' => $reversal->id,
            ]);

            return $reversal;
        });
    }
}
