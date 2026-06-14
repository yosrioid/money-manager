<?php

namespace App\Http\Controllers;

use App\Domain\Budgets\CalculateBudgetUsage;
use App\Domain\Transactions\SummarizeTransactionPeriod;
use App\Domain\Workspaces\WorkspaceContext;
use App\Enums\CategoryType;
use App\Http\Requests\DestroyCategoryBudgetOverrideRequest;
use App\Http\Requests\UpdateCategoryBudgetOverrideRequest;
use App\Models\Category;
use App\Models\CategoryBudgetOverride;
use App\Models\Workspace;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BudgetController extends Controller
{
    public function __construct(
        private readonly WorkspaceContext $workspaceContext,
    ) {}

    public function index(Request $request, CalculateBudgetUsage $calculateBudgetUsage, SummarizeTransactionPeriod $summarizeTransactionPeriod): Response
    {
        $this->authorize('viewAny', Category::class);

        $workspace = $this->workspaceContext->get();

        $month = $this->parseStrictMonth($request->query('month'), $workspace)
            ?? Carbon::now($workspace->timezone)->startOfMonth();

        $start = $summarizeTransactionPeriod->billingMonthStart($workspace, $month);

        return Inertia::render('budgets/Index', [
            'month' => $month->toDateString(),
            'periodStart' => $start->toDateString(),
            'budgets' => $calculateBudgetUsage->forMonth($workspace, $month),
            'previousMonth' => $month->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $month->copy()->addMonth()->format('Y-m'),
            'navigationShortcutsEnabled' => $workspace->navigation_shortcuts_enabled,
        ]);
    }

    public function income(Request $request, CalculateBudgetUsage $calculateBudgetUsage, SummarizeTransactionPeriod $summarizeTransactionPeriod): Response
    {
        $this->authorize('viewAny', Category::class);

        $workspace = $this->workspaceContext->get();

        $month = $this->parseStrictMonth($request->query('month'), $workspace)
            ?? Carbon::now($workspace->timezone)->startOfMonth();

        $start = $summarizeTransactionPeriod->billingMonthStart($workspace, $month);

        return Inertia::render('budgets/Income', [
            'month' => $month->toDateString(),
            'periodStart' => $start->toDateString(),
            'budgets' => $calculateBudgetUsage->forMonth($workspace, $month, CategoryType::Income),
            'previousMonth' => $month->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $month->copy()->addMonth()->format('Y-m'),
            'navigationShortcutsEnabled' => $workspace->navigation_shortcuts_enabled,
        ]);
    }

    public function trend(Request $request, CalculateBudgetUsage $calculateBudgetUsage): Response
    {
        $this->authorize('viewAny', Category::class);

        $workspace = $this->workspaceContext->get();

        $month = $this->parseStrictMonth($request->query('month'), $workspace)
            ?? Carbon::now($workspace->timezone)->startOfMonth();

        $months = 6;

        return Inertia::render('budgets/Trend', [
            'month' => $month->toDateString(),
            'expense' => $calculateBudgetUsage->trend($workspace, $month, $months),
            'income' => $calculateBudgetUsage->trend($workspace, $month, $months, CategoryType::Income),
            'previousMonth' => $month->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $month->copy()->addMonth()->format('Y-m'),
            'navigationShortcutsEnabled' => $workspace->navigation_shortcuts_enabled,
        ]);
    }

    public function weekly(Request $request, CalculateBudgetUsage $calculateBudgetUsage, SummarizeTransactionPeriod $summarizeTransactionPeriod): Response
    {
        $this->authorize('viewAny', Category::class);

        $workspace = $this->workspaceContext->get();

        $reference = $this->parseLocalDate($request->query('week'), $workspace);
        $weekStart = $summarizeTransactionPeriod->weekStart($workspace, $reference);

        return Inertia::render('budgets/Weekly', [
            'weekStart' => $weekStart->toDateString(),
            'budgets' => $calculateBudgetUsage->forWeek($workspace, $weekStart),
            'previousWeek' => $weekStart->copy()->subDays(7)->toDateString(),
            'nextWeek' => $weekStart->copy()->addDays(7)->toDateString(),
            'navigationShortcutsEnabled' => $workspace->navigation_shortcuts_enabled,
        ]);
    }

    public function yearly(Request $request, CalculateBudgetUsage $calculateBudgetUsage): Response
    {
        $this->authorize('viewAny', Category::class);

        $workspace = $this->workspaceContext->get();

        $year = $request->query('year');
        $year = is_string($year) && preg_match('/^\d{4}$/', $year)
            ? Carbon::createFromDate((int) $year, 1, 1, $workspace->timezone)
            : Carbon::now($workspace->timezone);

        $year = $year->startOfYear();

        return Inertia::render('budgets/Yearly', [
            'year' => $year->format('Y'),
            'budgets' => $calculateBudgetUsage->forYear($workspace, $year),
            'previousYear' => $year->copy()->subYear()->format('Y'),
            'nextYear' => $year->copy()->addYear()->format('Y'),
            'navigationShortcutsEnabled' => $workspace->navigation_shortcuts_enabled,
        ]);
    }

    public function updateOverride(UpdateCategoryBudgetOverrideRequest $request, Category $category): RedirectResponse
    {
        $workspace = $this->workspaceContext->get();

        CategoryBudgetOverride::query()->updateOrCreate(
            [
                'category_id' => $category->id,
                'period' => $request->validated('period'),
            ],
            [
                'workspace_id' => $workspace->id,
                'amount' => $request->validated('amount'),
            ],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Budget override saved.')]);

        return back();
    }

    public function destroyOverride(DestroyCategoryBudgetOverrideRequest $request, Category $category): RedirectResponse
    {
        $category->budgetOverrides()
            ->whereDate('period', $request->validated('period'))
            ->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Budget override removed.')]);

        return back();
    }

    private function parseStrictMonth(mixed $value, Workspace $workspace): ?Carbon
    {
        if (! is_string($value) || ! preg_match('/^(\d{4})-(\d{2})$/', $value, $matches)) {
            return null;
        }

        $month = (int) $matches[2];

        if ($month < 1 || $month > 12) {
            return null;
        }

        return Carbon::createFromDate((int) $matches[1], $month, 1, $workspace->timezone)->startOfMonth();
    }

    /**
     * Parse a workspace-local date query parameter, defaulting to the current
     * workspace-local date when missing or invalid.
     */
    private function parseLocalDate(mixed $value, Workspace $workspace): Carbon
    {
        return $this->parseStrictDate($value, $workspace) ?? Carbon::now($workspace->timezone)->startOfDay();
    }

    /**
     * Strictly parse a `YYYY-MM-DD` workspace-local date query parameter,
     * rejecting calendar-invalid dates and malformed values instead of
     * silently normalizing or throwing.
     */
    private function parseStrictDate(mixed $value, Workspace $workspace): ?Carbon
    {
        if (! is_string($value) || ! preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $matches)) {
            return null;
        }

        $date = Carbon::createFromDate((int) $matches[1], (int) $matches[2], (int) $matches[3], $workspace->timezone);

        if ($date->format('Y-m-d') !== $value) {
            return null;
        }

        return $date->startOfDay();
    }
}
