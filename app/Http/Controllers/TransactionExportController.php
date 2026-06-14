<?php

namespace App\Http\Controllers;

use App\Domain\Transactions\FilterTransactionsQuery;
use App\Domain\Workspaces\WorkspaceContext;
use App\Enums\TransactionExportStatus;
use App\Jobs\GenerateTransactionsExportFile;
use App\Models\TransactionExport;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransactionExportController extends Controller
{
    public function __construct(
        private readonly WorkspaceContext $workspaceContext,
    ) {}

    public function index(): Response
    {
        $this->authorize('viewAny', TransactionExport::class);

        $workspace = $this->workspaceContext->get();

        $exports = $workspace->transactionExports()
            ->orderByDesc('id')
            ->get();

        return Inertia::render('transactions/Exports', [
            'exports' => $exports->map(function (TransactionExport $export): array {
                $readyAt = $export->getRawOriginal('ready_at');

                return [
                    'id' => $export->id,
                    'status' => $export->getRawOriginal('status'),
                    'failed_reason' => $export->failed_reason,
                    'created_at' => $export->created_at?->toIso8601String(),
                    'ready_at' => $readyAt !== null ? Carbon::parse($readyAt)->toIso8601String() : null,
                ];
            })->all(),
        ]);
    }

    public function store(Request $request, FilterTransactionsQuery $filterTransactionsQuery): RedirectResponse
    {
        $this->authorize('create', TransactionExport::class);

        $workspace = $this->workspaceContext->get();
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        $filters = $filterTransactionsQuery->resolve($request, $workspace);

        $export = $workspace->transactionExports()->create([
            'created_by' => $user->id,
            'status' => TransactionExportStatus::Pending,
            'filters' => $filterTransactionsQuery->serialize($filters),
        ]);

        GenerateTransactionsExportFile::dispatch($export->id);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Export queued. It will appear here once ready.')]);

        return to_route('transactions.exports.index');
    }

    public function download(TransactionExport $transactionExport): StreamedResponse
    {
        abort_unless($transactionExport->workspace_id === $this->workspaceContext->get()->id, 404);
        $this->authorize('view', $transactionExport);

        abort_unless($transactionExport->getRawOriginal('status') === TransactionExportStatus::Ready->value && $transactionExport->file_path !== null, 404);

        return Storage::disk('local')->download($transactionExport->file_path, "transactions-export-{$transactionExport->id}.csv");
    }
}
