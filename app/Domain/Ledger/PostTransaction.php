<?php

namespace App\Domain\Ledger;

use App\Enums\LedgerEntryType;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
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
    ) {}

    /**
     * @param  array<int, array{account_id: ?int, category_id: ?int, type: LedgerEntryType, amount: int}>  $entries
     */
    public function post(Workspace $workspace, TransactionType $type, string $currencyCode, string $description, CarbonInterface $occurredAt, array $entries, User $actor): Transaction
    {
        if (! $workspace->memberships()->where('user_id', $actor->id)->exists()) {
            throw new AuthorizationException;
        }

        if (array_sum(array_column($entries, 'amount')) !== 0) {
            throw new LogicException('Posted transactions must balance to zero.');
        }

        return DB::transaction(function () use ($workspace, $type, $currencyCode, $description, $occurredAt, $entries, $actor): Transaction {
            $this->lockAccounts->lock(array_column($entries, 'account_id'));

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

            return $transaction;
        });
    }
}
