<?php

namespace App\Domain\Transactions;

use App\Enums\LedgerEntryType;
use App\Enums\TransactionStatus;
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

        return $this->forRange($workspace, $start, $start->copy()->addMonth());
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

        return $this->forRange($workspace, $start, $start->copy()->addDays(7));
    }

    /**
     * Summarize posted income and expense activity per workspace-local
     * billing month (see `billingMonthStart()`) within the given
     * workspace-local calendar year. Months are keyed by the calendar month
     * (`Y-m`) of their billing-period start.
     *
     * @return array<string, array{income: array<string, int>, expense: array<string, int>, net: array<string, int>, count: int}>
     */
    public function forYear(Workspace $workspace, CarbonInterface $year): array
    {
        $reference = Carbon::parse($year->toDateString(), $workspace->timezone)->startOfYear()->startOfDay();

        $boundaries = [];

        for ($i = 0; $i <= 12; $i++) {
            $boundaries[] = $this->billingMonthStart($workspace, $reference->copy()->addMonths($i));
        }

        $days = $this->forRange($workspace, $boundaries[0], $boundaries[12]);

        $months = [];

        foreach ($days as $date => $day) {
            $localDate = Carbon::parse($date, $workspace->timezone)->startOfDay();

            for ($i = 0; $i < 12; $i++) {
                if ($localDate->lt($boundaries[$i]) || $localDate->gte($boundaries[$i + 1])) {
                    continue;
                }

                $label = $reference->copy()->addMonths($i)->format('Y-m');
                $months[$label] ??= $this->emptyDaySummary();
                $months[$label]['count'] += $day['count'];

                foreach (['income', 'expense'] as $key) {
                    foreach ($day[$key] as $currency => $amount) {
                        $months[$label][$key][$currency] = ($months[$label][$key][$currency] ?? 0) + $amount;
                    }
                }

                break;
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
     * Resolve the workspace-local start of the week containing the given
     * date, honoring the workspace's `first_day_of_week` preference
     * (`0` = Sunday, `1` = Monday).
     */
    public function weekStart(Workspace $workspace, CarbonInterface $reference): Carbon
    {
        $reference = Carbon::parse($reference->toDateString(), $workspace->timezone)->startOfDay();

        $offset = ($reference->dayOfWeek - $workspace->first_day_of_week + 7) % 7;

        return $reference->subDays($offset);
    }

    /**
     * Resolve the workspace-local start of the billing month containing the
     * given date, honoring the workspace's `month_start_day` and
     * `adjust_month_for_weekend` preferences.
     *
     * When `month_start_day` falls on a weekend day that does not exist in a
     * given calendar month, or when `adjust_month_for_weekend` is enabled and
     * the start day falls on a weekend, the boundary moves to the preceding
     * Friday.
     */
    public function billingMonthStart(Workspace $workspace, CarbonInterface $reference): Carbon
    {
        $date = Carbon::parse($reference->toDateString(), $workspace->timezone)->startOfDay();

        for ($i = 0; $i < 40; $i++) {
            if ($this->isBillingMonthBoundary($workspace, $date)) {
                return $date;
            }

            $date = $date->copy()->subDay();
        }

        throw new \LogicException('Unable to resolve a billing month boundary.');
    }

    /**
     * Determine whether the given workspace-local date is the start of a
     * billing month, per `month_start_day` and `adjust_month_for_weekend`.
     *
     * A date is a boundary if it is the (possibly weekend-adjusted) start day
     * of its own calendar month, or if weekend adjustment pulled the
     * following calendar month's start day back onto this date.
     */
    private function isBillingMonthBoundary(Workspace $workspace, CarbonInterface $date): bool
    {
        if ($this->monthStartDayBoundary($workspace, $date)->isSameDay($date)) {
            return true;
        }

        return $this->monthStartDayBoundary($workspace, $date->copy()->addMonth())->isSameDay($date);
    }

    /**
     * Resolve the (possibly weekend-adjusted) `month_start_day` boundary
     * within the calendar month containing the given date.
     */
    private function monthStartDayBoundary(Workspace $workspace, CarbonInterface $referenceMonth): Carbon
    {
        $startDay = min($workspace->month_start_day, $referenceMonth->daysInMonth);
        $boundary = Carbon::instance($referenceMonth->copy()->day($startDay));

        if ($workspace->adjust_month_for_weekend && $boundary->isWeekend()) {
            $boundary = Carbon::instance($boundary->previous(Carbon::FRIDAY));
        }

        return $boundary;
    }

    /**
     * @return array<string, array{income: array<string, int>, expense: array<string, int>, net: array<string, int>, count: int}>
     */
    public function forRange(Workspace $workspace, CarbonInterface $start, CarbonInterface $end): array
    {
        $transactions = $workspace->transactions()
            ->whereNotNull('posted_at')
            ->where('include_in_statistics', true)
            ->where('status', '!=', TransactionStatus::Replaced->value)
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

            // Bucket by the account entry's sign rather than the transaction's
            // `type`, because a reversal or replacement keeps the original
            // `type` while moving money in the opposite direction.
            if ($accountEntry->amount >= 0) {
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
