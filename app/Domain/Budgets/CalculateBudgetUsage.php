<?php

namespace App\Domain\Budgets;

use App\Domain\Transactions\SummarizeTransactionPeriod;
use App\Enums\CategoryType;
use App\Enums\LedgerEntryType;
use App\Enums\TransactionStatus;
use App\Models\Category;
use App\Models\Workspace;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

class CalculateBudgetUsage
{
    public function __construct(
        private readonly SummarizeTransactionPeriod $summarizeTransactionPeriod,
    ) {}

    /**
     * Resolve the override amount for a category in the billing period
     * starting at `$periodStart`, if one exists.
     */
    public function resolveOverride(Category $category, CarbonInterface $periodStart): ?int
    {
        return $category->budgetOverrides()
            ->whereDate('period', $periodStart->toDateString())
            ->first()?->amount;
    }

    /**
     * Resolve the effective monthly budget for a category in the billing
     * period starting at `$periodStart`: an override for that period takes
     * precedence over the category's default `monthly_budget_amount`.
     */
    public function resolveBudget(Category $category, CarbonInterface $periodStart): ?int
    {
        return $this->resolveOverride($category, $periodStart) ?? $category->monthly_budget_amount;
    }

    /**
     * Summarize budget versus actual spending or income per active category
     * of the given `$type` for the workspace-local billing month containing
     * `$month`. Only categories with a default budget, an override for this
     * period, or actual activity are included.
     *
     * @return array<int, array{category_id: int, name: string, default_budget: ?int, override: ?int, carryover: ?int, budget: ?int, pace: ?int, actual: int, currency: ?string}>
     */
    public function forMonth(Workspace $workspace, CarbonInterface $month, CategoryType $type = CategoryType::Expense): array
    {
        $start = $this->summarizeTransactionPeriod->billingMonthStart($workspace, $month);
        $end = $this->summarizeTransactionPeriod->billingMonthStart($workspace, $month->copy()->addMonth());

        $previousStart = $this->summarizeTransactionPeriod->billingMonthStart($workspace, $month->copy()->subMonth());
        $previous = $this->forRange($workspace, $previousStart, $start, $type);

        $previousByCategory = [];

        foreach ($previous as $row) {
            $previousByCategory[$row['category_id']] = ['budget' => $row['budget'], 'actual' => $row['actual']];
        }

        return $this->forRange($workspace, $start, $end, $type, $previousByCategory);
    }

    /**
     * Summarize budget versus actual spending or income per active category
     * of the given `$type` for the given workspace-local range. The budget
     * is resolved against the billing period starting at `$start`. For
     * categories with `budget_carryover_enabled`, the budget is adjusted by
     * the unused (positive) or overspent (negative) amount from the
     * immediately preceding period, looked up in `$previousPeriod` (keyed by
     * `category_id`, each a `forRange()` row's `budget`/`actual`), via
     * `carryover`. Each row also includes `pace`: the portion of `budget`
     * (after carryover) expected to be used by "today" (the current
     * workspace-local date), prorated by the number of elapsed days in
     * `[$start, $end)`.
     *
     * @param  array<int, array{budget: ?int, actual: int}>  $previousPeriod
     * @return array<int, array{category_id: int, name: string, default_budget: ?int, override: ?int, carryover: ?int, budget: ?int, pace: ?int, actual: int, currency: ?string}>
     */
    public function forRange(Workspace $workspace, CarbonInterface $start, CarbonInterface $end, CategoryType $type = CategoryType::Expense, array $previousPeriod = []): array
    {
        $actuals = $this->actualByCategory($workspace, $start, $end, $type);
        $elapsedDays = $this->elapsedDays($workspace, $start, $end);

        $categories = $workspace->categories()
            ->where('type', $type)
            ->active()
            ->orderBy('position')
            ->get();

        $rows = [];

        foreach ($categories as $category) {
            $override = $this->resolveOverride($category, $start);
            $baseBudget = $override ?? $category->monthly_budget_amount;
            $actual = $actuals[$category->id] ?? null;

            if ($baseBudget === null && $actual === null) {
                continue;
            }

            $carryover = null;
            $budget = $baseBudget;

            if ($category->budget_carryover_enabled && $baseBudget !== null && isset($previousPeriod[$category->id])) {
                $previousBudget = $previousPeriod[$category->id]['budget'];

                if ($previousBudget !== null) {
                    $carryover = $previousBudget - $previousPeriod[$category->id]['actual'];
                    $budget = $baseBudget + $carryover;
                }
            }

            $rows[] = [
                'category_id' => $category->id,
                'name' => $category->name,
                'default_budget' => $category->monthly_budget_amount,
                'override' => $override,
                'carryover' => $carryover,
                'budget' => $budget,
                'pace' => $budget === null ? null : (int) round($budget * $elapsedDays['elapsed'] / $elapsedDays['total']),
                'actual' => $actual['amount'] ?? 0,
                'currency' => $actual['currency'] ?? null,
            ];
        }

        return $rows;
    }

    /**
     * Determine how many of the days in `[$start, $end)` have elapsed as of
     * "today" (the current workspace-local date): 0 if the period has not
     * started yet, the full day count if it has already ended, and the
     * number of days from `$start` up to and including today otherwise.
     *
     * @return array{elapsed: int, total: int}
     */
    private function elapsedDays(Workspace $workspace, CarbonInterface $start, CarbonInterface $end): array
    {
        $periodStart = $start->copy()->startOfDay();
        $periodEnd = $end->copy()->startOfDay();
        $total = (int) $periodStart->diffInDays($periodEnd);

        $today = Carbon::now($workspace->timezone)->startOfDay();

        if ($today->lt($periodStart)) {
            $elapsed = 0;
        } elseif ($today->gte($periodEnd)) {
            $elapsed = $total;
        } else {
            $elapsed = (int) $periodStart->diffInDays($today) + 1;
        }

        return ['elapsed' => $elapsed, 'total' => $total];
    }

    /**
     * Summarize budget versus actual spending or income per active category
     * of the given `$type` for the workspace-local week containing
     * `$reference` (per `SummarizeTransactionPeriod::weekStart()`). The
     * weekly budget is the category's effective monthly budget (default or
     * override) for the billing month containing the week, prorated to 7
     * days based on the number of days in that billing month.
     *
     * @return array<int, array{category_id: int, name: string, budget: ?int, actual: int, currency: ?string}>
     */
    public function forWeek(Workspace $workspace, CarbonInterface $reference, CategoryType $type = CategoryType::Expense): array
    {
        $weekStart = $this->summarizeTransactionPeriod->weekStart($workspace, $reference);
        $weekEnd = $weekStart->copy()->addDays(7);

        $billingMonthStart = $this->summarizeTransactionPeriod->billingMonthStart($workspace, $weekStart);
        $billingMonthEnd = $this->summarizeTransactionPeriod->billingMonthStart($workspace, $weekStart->copy()->addMonth());
        $daysInBillingMonth = $billingMonthStart->diffInDays($billingMonthEnd);

        $actuals = $this->actualByCategory($workspace, $weekStart, $weekEnd, $type);

        $categories = $workspace->categories()
            ->where('type', $type)
            ->active()
            ->orderBy('position')
            ->get();

        $rows = [];

        foreach ($categories as $category) {
            $monthlyBudget = $this->resolveBudget($category, $billingMonthStart);
            $budget = $monthlyBudget === null ? null : (int) round($monthlyBudget * 7 / $daysInBillingMonth);
            $actual = $actuals[$category->id] ?? null;

            if ($budget === null && $actual === null) {
                continue;
            }

            $rows[] = [
                'category_id' => $category->id,
                'name' => $category->name,
                'budget' => $budget,
                'actual' => $actual['amount'] ?? 0,
                'currency' => $actual['currency'] ?? null,
            ];
        }

        return $rows;
    }

    /**
     * Summarize budget versus actual spending or income per active category
     * of the given `$type` for the workspace-local calendar year containing
     * `$year`. The budget is the sum of each of the year's 12 billing-month
     * budgets (see `forMonth()`), so a `P4-02` override in any month is
     * reflected in the yearly total.
     *
     * @return array<int, array{category_id: int, name: string, budget: ?int, actual: int, currency: ?string}>
     */
    public function forYear(Workspace $workspace, CarbonInterface $year, CategoryType $type = CategoryType::Expense): array
    {
        $reference = Carbon::parse($year->toDateString(), $workspace->timezone)->startOfYear();

        $totals = [];

        for ($i = 0; $i < 12; $i++) {
            $rows = $this->forMonth($workspace, $reference->copy()->addMonths($i), $type);

            foreach ($rows as $row) {
                $totals[$row['category_id']] ??= [
                    'category_id' => $row['category_id'],
                    'name' => $row['name'],
                    'budget' => null,
                    'actual' => 0,
                    'currency' => null,
                ];

                if ($row['budget'] !== null) {
                    $totals[$row['category_id']]['budget'] = ($totals[$row['category_id']]['budget'] ?? 0) + $row['budget'];
                }

                $totals[$row['category_id']]['actual'] += $row['actual'];

                if ($row['currency'] !== null) {
                    $totals[$row['category_id']]['currency'] = $row['currency'];
                }
            }
        }

        return array_values($totals);
    }

    /**
     * Reduce a set of `forMonth()`/`forRange()` rows to a single total:
     * `budget` is the sum of each row's non-null budget (or `null` if no row
     * has a budget), `actual` is the sum of all rows' actual amounts.
     *
     * @param  array<int, array{budget: ?int, actual: int, currency: ?string}>  $rows
     * @return array{budget: ?int, actual: int, currency: ?string}
     */
    public function summarizeTotal(array $rows): array
    {
        $budget = null;
        $actual = 0;
        $currency = null;

        foreach ($rows as $row) {
            if ($row['budget'] !== null) {
                $budget = ($budget ?? 0) + $row['budget'];
            }

            $actual += $row['actual'];

            if ($row['currency'] !== null) {
                $currency = $row['currency'];
            }
        }

        return ['budget' => $budget, 'actual' => $actual, 'currency' => $currency];
    }

    /**
     * Summarize the total budget versus actual spending or income of the
     * given `$type` for each of the `$months` workspace-local billing months
     * up to and including the billing month containing `$reference`, oldest
     * first.
     *
     * @return array<int, array{month: string, budget: ?int, actual: int, currency: ?string}>
     */
    public function trend(Workspace $workspace, CarbonInterface $reference, int $months, CategoryType $type = CategoryType::Expense): array
    {
        $rows = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $month = $reference->copy()->subMonths($i);
            $start = $this->summarizeTransactionPeriod->billingMonthStart($workspace, $month);

            $rows[] = array_merge(
                ['month' => $start->format('Y-m')],
                $this->summarizeTotal($this->forMonth($workspace, $month, $type)),
            );
        }

        return $rows;
    }

    /**
     * Sum posted category-entry activity per category over the given range.
     * For expense categories, the category entry amount is positive and is
     * the actual spend. For income categories, the category entry amount is
     * negative and its absolute value is the actual income.
     *
     * @return array<int, array{amount: int, currency: string}>
     */
    private function actualByCategory(Workspace $workspace, CarbonInterface $start, CarbonInterface $end, CategoryType $type): array
    {
        $entries = $workspace->transactionEntries()
            ->where('type', LedgerEntryType::Category)
            ->where('amount', $type === CategoryType::Expense ? '>' : '<', 0)
            ->whereHas('transaction', fn (Builder $query): Builder => $query->whereNotNull('posted_at')
                ->where('include_in_statistics', true)
                ->where('status', '!=', TransactionStatus::Replaced)
                ->where('occurred_at', '>=', $start->copy()->utc())
                ->where('occurred_at', '<', $end->copy()->utc()))
            ->get();

        $actuals = [];

        foreach ($entries as $entry) {
            $categoryId = $entry->category_id;

            if ($categoryId === null) {
                continue;
            }

            $actuals[$categoryId] ??= ['amount' => 0, 'currency' => $entry->currency_code];
            $actuals[$categoryId]['amount'] += $type === CategoryType::Expense ? $entry->amount : -$entry->amount;
        }

        return $actuals;
    }
}
