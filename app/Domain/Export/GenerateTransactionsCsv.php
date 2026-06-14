<?php

namespace App\Domain\Export;

use App\Enums\LedgerEntryType;
use App\Models\Transaction;
use App\Models\TransactionEntry;
use App\Models\Workspace;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GenerateTransactionsCsv
{
    /**
     * Stream the given filtered transaction query as a CSV download, one row
     * per account-side ledger entry.
     *
     * @param  HasMany<Transaction, Workspace>  $transactions
     */
    public function stream(Workspace $workspace, HasMany $transactions): StreamedResponse
    {
        $filename = 'transactions-'.Carbon::now($workspace->timezone)->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($transactions, $workspace): void {
            $handle = fopen('php://output', 'w');

            $this->write($handle, $workspace, $transactions);

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * Write the given filtered transaction query as CSV to the file at the
     * given path, one row per account-side ledger entry. Used by the queued
     * export job for large downloads.
     *
     * @param  HasMany<Transaction, Workspace>  $transactions
     */
    public function writeToFile(string $path, Workspace $workspace, HasMany $transactions): void
    {
        $handle = fopen($path, 'w');

        $this->write($handle, $workspace, $transactions);

        fclose($handle);
    }

    /**
     * @param  resource  $handle
     * @param  HasMany<Transaction, Workspace>  $transactions
     */
    private function write($handle, Workspace $workspace, HasMany $transactions): void
    {
        fputcsv($handle, ['Date', 'Type', 'Status', 'Description', 'Memo', 'Merchant', 'Account', 'Category', 'Amount', 'Currency', 'Tags']);

        $transactions->with(['merchant', 'tags', 'entries.account', 'entries.category'])
            ->orderBy('transactions.id')
            ->chunkById(500, function ($chunk) use ($handle, $workspace): void {
                foreach ($chunk as $transaction) {
                    $this->writeTransaction($handle, $transaction, $workspace);
                }
            }, 'transactions.id', 'id');
    }

    /**
     * @param  resource  $handle
     */
    private function writeTransaction($handle, Transaction $transaction, Workspace $workspace): void
    {
        $occurredAt = Carbon::parse($transaction->getRawOriginal('occurred_at'))->setTimezone($workspace->timezone);

        $merchant = $transaction->merchant_id === null ? '' : $transaction->merchant->name;
        $tags = $transaction->tags->pluck('name')->implode(', ');

        $categoryEntry = $transaction->entries->first(
            fn (TransactionEntry $entry): bool => $entry->getRawOriginal('type') === LedgerEntryType::Category->value
        );
        $category = $categoryEntry === null || $categoryEntry->category_id === null ? '' : $categoryEntry->category->name;

        foreach ($transaction->entries as $entry) {
            if ($entry->getRawOriginal('type') !== LedgerEntryType::Account->value) {
                continue;
            }

            fputcsv($handle, [
                $occurredAt->toDateString(),
                $transaction->getRawOriginal('type'),
                $transaction->getRawOriginal('status'),
                $this->escapeFormula($transaction->description),
                $this->escapeFormula($transaction->memo),
                $this->escapeFormula($merchant),
                $this->escapeFormula($entry->account->name),
                $this->escapeFormula($category),
                $entry->amount,
                $entry->currency_code,
                $this->escapeFormula($tags),
            ]);
        }
    }

    /**
     * Prefix values that would be interpreted as formulas by spreadsheet
     * applications (Excel, Google Sheets, LibreOffice) with a single quote,
     * preventing CSV formula injection from user-controlled text fields.
     */
    private function escapeFormula(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        return in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)
            ? "'".$value
            : $value;
    }
}
