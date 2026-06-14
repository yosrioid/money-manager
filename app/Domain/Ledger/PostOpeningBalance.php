<?php

namespace App\Domain\Ledger;

use App\Enums\LedgerEntryType;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use LogicException;

class PostOpeningBalance
{
    public function post(Account $account, int $amount, User $actor): ?Transaction
    {
        if ($amount === 0) {
            return null;
        }

        return DB::transaction(function () use ($account, $amount, $actor): Transaction {
            $lockedAccount = Account::query()
                ->whereKey($account->id)
                ->where('workspace_id', $account->workspace_id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $lockedAccount->workspace->memberships()->where('user_id', $actor->id)->exists()) {
                throw new AuthorizationException;
            }

            if ($lockedAccount->ledgerEntries()->exists()) {
                throw new LogicException('An opening balance can only be posted to an account without ledger entries.');
            }

            $postedAt = now();
            $transaction = Transaction::query()->create([
                'workspace_id' => $lockedAccount->workspace_id,
                'created_by' => $actor->id,
                'type' => TransactionType::OpeningBalance,
                'status' => TransactionStatus::Draft,
                'currency_code' => $lockedAccount->currency_code,
                'description' => "Opening balance for {$lockedAccount->name}",
                'occurred_at' => $postedAt,
                'posted_at' => null,
            ]);

            $transaction->entries()->createMany([
                [
                    'workspace_id' => $lockedAccount->workspace_id,
                    'account_id' => $lockedAccount->id,
                    'type' => LedgerEntryType::Account,
                    'currency_code' => $lockedAccount->currency_code,
                    'amount' => $amount,
                    'base_amount' => $amount,
                ],
                [
                    'workspace_id' => $lockedAccount->workspace_id,
                    'account_id' => null,
                    'type' => LedgerEntryType::OpeningBalanceEquity,
                    'currency_code' => $lockedAccount->currency_code,
                    'amount' => -$amount,
                    'base_amount' => -$amount,
                ],
            ]);

            if ($transaction->entries()->sum('amount') !== 0) {
                throw new LogicException('Posted transactions must balance to zero.');
            }

            $transaction->update([
                'status' => TransactionStatus::Posted,
                'posted_at' => $postedAt,
            ]);

            return $transaction;
        });
    }
}
