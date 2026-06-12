<?php

namespace App\Domain\Transactions;

use App\Enums\LedgerEntryType;
use App\Enums\TransactionType;
use App\Models\Workspace;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class SummarizeTransactionPeriod
{
    /**
     * Summarize posted income and expense activity per workspace-local date
     * within the given workspace-local month.
     *
     * @return array<string, array{income: array<string, int>, expense: array<string, int>, net: array<string, int>, count: int}>
     */
    public function forMonth(Workspace $workspace, CarbonInterface $month): array
    {
        $start = Carbon::parse($month->toDateString(), $workspace->timezone)->startOfMonth()->startOfDay();

        return $this->summarize($workspace, $start, $start->copy()->addMonth());
    }

    /**
     * Summarize posted income and expense activity per workspace-local date
     * for the 7-day period starting on the given workspace-local date.
     *
     * @return array<string, array{income: array<string, int>, expense: array<string, int>, net: array<string, int>, count: int}>
     */
    public function forWeek(Workspace $workspace, CarbonInterface $weekStart): array
    {
        $start = Carbon::parse($weekStart->toDateString(), $workspace->timezone)->startOfDay();

        return $this->summarize($workspace, $start, $start->copy()->addDays(7));
    }

    /**
     * Summarize posted income and expense activity per workspace-local month
     * within the given workspace-local year.
     *
     * @return array<string, array{income: array<string, int>, expense: array<string, int>, net: array<string, int>, count: int}>
     */
    public function forYear(Workspace $workspace, CarbonInterface $year): array
    {
        $start = Carbon::parse($year->toDateString(), $workspace->timezone)->startOfYear()->startOfDay();

        $days = $this->summarize($workspace, $start, $start->copy()->addYear());

        $months = [];

        foreach ($days as $date => $day) {
            $month = substr($date, 0, 7);
            $months[$month] ??= $this->emptyDaySummary();
            $months[$month]['count'] += $day['count'];

            foreach (['income', 'expense'] as $key) {
                foreach ($day[$key] as $currency => $amount) {
                    $months[$month][$key][$currency] = ($months[$month][$key][$currency] ?? 0) + $amount;
                }
            }
        }

        foreach ($months as $month => $data) {
            $currencies = array_unique([...array_keys($data['income']), ...array_keys($data['expense'])]);

            foreach ($currencies as $currency) {
                $months[$month]['net'][$currency] = ($data['income'][$currency] ?? 0) - ($data['expense'][$currency] ?? 0);
            }
        }

        ksort($months);

        return $months;
    }

    /**
     * @return array<string, array{income: array<string, int>, expense: array<string, int>, net: array<string, int>, count: int}>
     */
    private function summarize(Workspace $workspace, CarbonInterface $start, CarbonInterface $end): array
    {
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
