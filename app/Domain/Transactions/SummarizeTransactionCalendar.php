<?php

namespace App\Domain\Transactions;

use App\Enums\LedgerEntryType;
use App\Enums\TransactionType;
use App\Models\Workspace;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class SummarizeTransactionCalendar
{
    /**
     * Summarize posted income and expense activity per workspace-local date
     * within the given workspace-local month.
     *
     * @return array<string, array{income: array<string, int>, expense: array<string, int>, net: array<string, int>, count: int}>
     */
    public function summarize(Workspace $workspace, CarbonInterface $month): array
    {
        $start = Carbon::parse($month->toDateString(), $workspace->timezone)->startOfMonth()->startOfDay();
        $end = $start->copy()->addMonth();

        $transactions = $workspace->transactions()
            ->whereNotNull('posted_at')
            ->where('occurred_at', '>=', $start->copy()->utc())
            ->where('occurred_at', '<', $end->copy()->utc())
            ->with('entries')
            ->get();

        $days = [];

        foreach ($transactions as $transaction) {
            $type = $transaction->getRawOriginal('type');

            $localDate = Carbon::parse($transaction->getRawOriginal('occurred_at'))
                ->setTimezone($workspace->timezone)
                ->toDateString();

            $days[$localDate] ??= $this->emptyDaySummary();
            $days[$localDate]['count']++;

            if (! in_array($type, [TransactionType::Income->value, TransactionType::Expense->value], true)) {
                continue;
            }

            $accountEntry = $transaction->entries->firstWhere('type', LedgerEntryType::Account);

            if ($accountEntry === null) {
                continue;
            }

            $currency = $accountEntry->currency_code;

            if ($type === TransactionType::Income->value) {
                $days[$localDate]['income'][$currency] = ($days[$localDate]['income'][$currency] ?? 0) + $accountEntry->amount;
            } else {
                $days[$localDate]['expense'][$currency] = ($days[$localDate]['expense'][$currency] ?? 0) + abs($accountEntry->amount);
            }
        }

        foreach ($days as $date => $day) {
            $currencies = array_unique([...array_keys($day['income']), ...array_keys($day['expense'])]);

            foreach ($currencies as $currency) {
                $days[$date]['net'][$currency] = ($day['income'][$currency] ?? 0) - ($day['expense'][$currency] ?? 0);
            }
        }

        return $days;
    }

    /**
     * @return array{income: array<string, int>, expense: array<string, int>, net: array<string, int>, count: int}
     */
    private function emptyDaySummary(): array
    {
        return ['income' => [], 'expense' => [], 'net' => [], 'count' => 0];
    }
}
