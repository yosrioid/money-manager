<?php

namespace App\Http\Controllers;

use App\Domain\Transactions\RecordIncomeExpense;
use App\Domain\Workspaces\WorkspaceContext;
use App\Enums\TransactionType;
use App\Http\Requests\StoreTransactionRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function __construct(
        private readonly WorkspaceContext $workspaceContext,
    ) {}

    public function create(): Response
    {
        $workspace = $this->workspaceContext->get();

        return Inertia::render('transactions/CreateTransaction', [
            'accounts' => $workspace->accounts()->active()->orderBy('name')->get(['id', 'name', 'currency_code']),
            'categories' => $workspace->categories()->active()->orderBy('name')->get(['id', 'name', 'type']),
        ]);
    }

    public function store(StoreTransactionRequest $request, RecordIncomeExpense $recordIncomeExpense): RedirectResponse
    {
        $workspace = $this->workspaceContext->get();
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        $validated = $request->validated();

        $account = $workspace->accounts()->whereKey($validated['account_id'])->firstOrFail();
        $category = $workspace->categories()->whereKey($validated['category_id'])->firstOrFail();

        $recordIncomeExpense->record(
            $account,
            $category,
            TransactionType::from($validated['type']),
            (int) $validated['amount'],
            $validated['description'],
            Carbon::parse($validated['occurred_at']),
            $user,
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Transaction recorded.')]);

        return to_route('accounts.index');
    }
}
