<?php

namespace App\Http\Controllers;

use App\Domain\Export\GenerateReportSpreadsheet;
use App\Domain\Ledger\CalculateNetAsset;
use App\Domain\Reports\GenerateTransactionReport;
use App\Domain\Transactions\SummarizeTransactionPeriod;
use App\Domain\Workspaces\WorkspaceContext;
use App\Enums\CategoryType;
use App\Models\Transaction;
use App\Models\Workspace;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        private readonly WorkspaceContext $workspaceContext,
    ) {}

    public function index(Request $request, GenerateTransactionReport $report, SummarizeTransactionPeriod $summarizeTransactionPeriod, CalculateNetAsset $calculateNetAsset): Response
    {
        $this->authorize('viewAny', Transaction::class);

        $workspace = $this->workspaceContext->get();

        $month = $this->parseStrictMonth($request->query('month'), $workspace)
            ?? Carbon::now($workspace->timezone)->startOfMonth();

        $start = $summarizeTransactionPeriod->billingMonthStart($workspace, $month);
        $end = $summarizeTransactionPeriod->billingMonthStart($workspace, $month->copy()->addMonth());

        $filters = $this->resolveFilters($request);
        $visibleWidgets = $workspace->reportWidgets();

        $props = [
            'month' => $month->toDateString(),
            'previousMonth' => $month->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $month->copy()->addMonth()->format('Y-m'),
            'filters' => $filters,
            'filterOptions' => [
                'accounts' => $workspace->accounts()->active()->orderBy('name')->get(['id', 'name']),
                'categories' => $workspace->categories()->whereNull('parent_id')->orderBy('name')->get(['id', 'name', 'type']),
                'merchants' => $workspace->merchants()->orderBy('name')->get(['id', 'name']),
                'tags' => $workspace->tags()->orderBy('name')->get(['id', 'name']),
            ],
            'visibleWidgets' => $visibleWidgets,
            'navigationShortcutsEnabled' => $workspace->navigation_shortcuts_enabled,
        ];

        if (in_array('summary', $visibleWidgets, true)) {
            $props['summary'] = Inertia::defer(fn () => $report->summary($workspace, $start, $end, $filters), 'reports');
        }

        if (in_array('comparison', $visibleWidgets, true)) {
            $props['comparison'] = Inertia::defer(fn () => $report->comparison($workspace, $start, $end, $filters), 'reports');
        }

        if (in_array('categoryBreakdown', $visibleWidgets, true)) {
            $props['categoryBreakdown'] = Inertia::defer(fn () => [
                'expense' => $report->byCategory($workspace, $start, $end, CategoryType::Expense, $filters),
                'income' => $report->byCategory($workspace, $start, $end, CategoryType::Income, $filters),
            ], 'reports');
        }

        if (in_array('merchantBreakdown', $visibleWidgets, true)) {
            $props['merchantBreakdown'] = Inertia::defer(fn () => $report->byMerchant($workspace, $start, $end, $filters), 'reports');
        }

        if (in_array('accountActivity', $visibleWidgets, true)) {
            $props['accountActivity'] = Inertia::defer(fn () => $report->byAccount($workspace, $start, $end, $filters), 'reports');
        }

        if (in_array('netWorth', $visibleWidgets, true)) {
            $props['netWorth'] = Inertia::defer(fn () => $calculateNetAsset->summary($workspace), 'net-worth');
        }

        if (in_array('netWorthTrend', $visibleWidgets, true)) {
            $props['netWorthTrend'] = Inertia::defer(fn () => $calculateNetAsset->trend($workspace, $month), 'net-worth');
        }

        return Inertia::render('reports/Index', $props);
    }

    public function export(Request $request, GenerateTransactionReport $report, SummarizeTransactionPeriod $summarizeTransactionPeriod, CalculateNetAsset $calculateNetAsset, GenerateReportSpreadsheet $generateReportSpreadsheet): StreamedResponse
    {
        $this->authorize('viewAny', Transaction::class);

        $workspace = $this->workspaceContext->get();

        $month = $this->parseStrictMonth($request->query('month'), $workspace)
            ?? Carbon::now($workspace->timezone)->startOfMonth();

        $start = $summarizeTransactionPeriod->billingMonthStart($workspace, $month);
        $end = $summarizeTransactionPeriod->billingMonthStart($workspace, $month->copy()->addMonth());

        $filters = $this->resolveFilters($request);

        $spreadsheet = $generateReportSpreadsheet->forMonth($workspace, $start, $end, $filters);

        return $generateReportSpreadsheet->stream($spreadsheet, "report-{$month->format('Y-m')}.xlsx");
    }

    /**
     * Resolve and validate the report filters from the request query string.
     *
     * @return array{account_ids: array<int, int>, category_ids: array<int, int>, merchant_id: ?int, tag_ids: array<int, int>}
     */
    private function resolveFilters(Request $request): array
    {
        return [
            'account_ids' => $this->parseIdList($request->query('account_ids')),
            'category_ids' => $this->parseIdList($request->query('category_ids')),
            'merchant_id' => $this->parseId($request->query('merchant_id')),
            'tag_ids' => $this->parseIdList($request->query('tag_ids')),
        ];
    }

    /**
     * @return array<int, int>
     */
    private function parseIdList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(
            fn (mixed $id): ?int => $this->parseId($id),
            $value,
        ))));
    }

    private function parseId(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    /**
     * Strictly parse a `YYYY-MM` workspace-local month query parameter,
     * rejecting malformed values (e.g. `2026-99`) instead of letting them
     * reach the date parser.
     */
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
}
