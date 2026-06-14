<?php

namespace App\Http\Controllers;

use App\Domain\Import\ImportTransactions;
use App\Domain\Import\ParseTransactionImportFile;
use App\Domain\Import\ValidateTransactionImportRows;
use App\Domain\Workspaces\WorkspaceContext;
use App\Http\Requests\PreviewTransactionImportRequest;
use App\Http\Requests\StoreTransactionImportRequest;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ImportController extends Controller
{
    public function __construct(
        private readonly WorkspaceContext $workspaceContext,
    ) {}

    public function create(): Response
    {
        $this->authorize('create', Transaction::class);

        return Inertia::render('imports/Transactions');
    }

    public function preview(PreviewTransactionImportRequest $request, ParseTransactionImportFile $parser, ValidateTransactionImportRows $validator): Response
    {
        $workspace = $this->workspaceContext->get();

        $file = $request->file('file');
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $token = Str::uuid()->toString().'.'.$extension;

        $path = $file->storeAs("imports/{$workspace->id}", $token, 'local');

        $rows = $validator->validate($workspace, $parser->parse(Storage::disk('local')->path($path), $extension));

        return Inertia::render('imports/Transactions', [
            'token' => $token,
            'rows' => array_map(fn (array $row): array => [
                'row' => $row['row'],
                'status' => $row['status'],
                'errors' => $row['errors'],
                'preview' => $row['preview'],
            ], $rows),
            'summary' => [
                'total' => count($rows),
                'valid' => count(array_filter($rows, fn (array $row): bool => $row['status'] === 'valid')),
                'invalid' => count(array_filter($rows, fn (array $row): bool => $row['status'] === 'error')),
            ],
        ]);
    }

    public function store(StoreTransactionImportRequest $request, ParseTransactionImportFile $parser, ValidateTransactionImportRows $validator, ImportTransactions $importTransactions): RedirectResponse
    {
        $workspace = $this->workspaceContext->get();
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        $token = $request->string('token')->toString();
        $path = "imports/{$workspace->id}/{$token}";

        abort_unless(Storage::disk('local')->exists($path), 404);

        $extension = (string) pathinfo($token, PATHINFO_EXTENSION);
        $rows = $validator->validate($workspace, $parser->parse(Storage::disk('local')->path($path), $extension));

        $summary = $importTransactions->import($workspace, $user, $rows);

        Storage::disk('local')->delete($path);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __(':imported imported, :duplicated already imported, :invalid invalid rows skipped.', $summary),
        ]);

        return to_route('transactions.index');
    }
}
