<?php

namespace App\Domain\Ledger;

use App\Enums\LedgerEntryType;
use App\Enums\TransactionType;
use App\Models\Currency;
use App\Models\Transaction;

class CalculateExchangeDifference
{
    /**
     * For a cross-currency transfer, compare the destination entry's actual
     * amount against the amount implied by the transaction's recorded
     * exchange rate, returning the difference in the destination currency.
     *
     * The recorded `exchange_rate` is the number of source-currency major
     * units per one destination-currency major unit.
     *
     * Returns `null` for transactions that are not cross-currency transfers
     * (no recorded exchange rate).
     *
     * @return array{amount: int, currency_code: string}|null
     */
    public function calculate(Transaction $transaction): ?array
    {
        if ($transaction->getRawOriginal('type') !== TransactionType::Transfer->value) {
            return null;
        }

        $exchangeRate = $transaction->getRawOriginal('exchange_rate');

        if ($exchangeRate === null) {
            return null;
        }

        $sourceEntry = $transaction->entries
            ->where('type', LedgerEntryType::Account)
            ->first(fn ($entry): bool => $entry->amount < 0);

        $destinationEntry = $transaction->entries
            ->where('type', LedgerEntryType::Account)
            ->first(fn ($entry): bool => $entry->amount > 0);

        if ($sourceEntry === null || $destinationEntry === null) {
            return null;
        }

        $sourceDecimals = (int) Currency::query()->whereKey($sourceEntry->currency_code)->value('decimal_places');
        $destinationDecimals = (int) Currency::query()->whereKey($destinationEntry->currency_code)->value('decimal_places');

        $impliedAmount = (int) round(
            $destinationEntry->base_amount * 10 ** ($destinationDecimals - $sourceDecimals) / (float) $exchangeRate
        );

        return [
            'amount' => $destinationEntry->amount - $impliedAmount,
            'currency_code' => $destinationEntry->currency_code,
        ];
    }
}
