<?php

namespace App\Domain\Transactions;

use App\Enums\LedgerEntryType;
use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;

class DuplicateTransaction
{
    public function __construct(private readonly SaveTransactionDraft $saveTransactionDraft) {}

    public function duplicate(Transaction $transaction, User $actor): Transaction
    {
        $transaction->loadMissing('entries', 'tags', 'workspace');
        $accountEntries = $transaction->entries->where('type', LedgerEntryType::Account);
        $categoryEntries = $transaction->entries->where('type', LedgerEntryType::Category);
        $sourceAccount = $accountEntries->first();
        $destinationAccount = $accountEntries->skip(1)->first();

        $data = [
            'source_transaction_id' => $transaction->id,
            'type' => $transaction->getRawOriginal('type'),
            'account_id' => $sourceAccount?->account_id,
            'amount' => (string) abs((int) $sourceAccount?->amount),
            'description' => $transaction->description,
            'memo' => $transaction->memo,
            'merchant_id' => $transaction->merchant_id,
            'tag_ids' => $transaction->tags->pluck('id')->all(),
            'occurred_at' => Carbon::parse($transaction->getRawOriginal('occurred_at'))->setTimezone($transaction->workspace->timezone)->format('Y-m-d\TH:i'),
        ];

        if ($transaction->getRawOriginal('type') === TransactionType::Transfer->value) {
            $feeEntry = $categoryEntries->first();
            $data['amount'] = (string) abs((int) $destinationAccount?->amount);
            $data['destination_account_id'] = $destinationAccount?->account_id;
            $data['fee_amount'] = $feeEntry ? (string) abs($feeEntry->amount) : null;
            $data['fee_category_id'] = $feeEntry?->category_id;
        } elseif ($categoryEntries->count() === 1) {
            $data['category_id'] = $categoryEntries->first()?->category_id;
        } else {
            $data['splits'] = $categoryEntries->map(fn ($entry): array => [
                'category_id' => $entry->category_id,
                'amount' => (string) abs($entry->amount),
            ])->values()->all();
        }

        return $this->saveTransactionDraft->save($transaction->workspace, $actor, $data);
    }
}
