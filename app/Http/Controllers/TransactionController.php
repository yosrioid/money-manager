<?php

namespace App\Http\Controllers;

use App\Domain\Transactions\RecordIncomeExpense;
use App\Domain\Transactions\RecordTransfer;
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
            'merchants' => $workspace->merchants()->active()->orderBy('name')->get(['id', 'name']),
            'tags' => $workspace->tags()->active()->orderBy('name')->get(['id', 'name', 'color']),
            'timezone' => $workspace->timezone,
        ]);
    }

    public function store(StoreTransactionRequest $request, RecordIncomeExpense $recordIncomeExpense, RecordTransfer $recordTransfer): RedirectResponse
    {
        $workspace = $this->workspaceContext->get();
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        $validated = $request->validated();

        $account = $workspace->accounts()->whereKey($validated['account_id'])->firstOrFail();
        $type = TransactionType::from($validated['type']);
        $merchant = isset($validated['merchant_id'])
            ? $workspace->merchants()->whereKey($validated['merchant_id'])->firstOrFail()
            : null;
        $tags = $workspace->tags()->whereKey($validated['tag_ids'] ?? [])->get()->all();
        $occurredAt = Carbon::parse($validated['occurred_at'], $workspace->timezone)->utc();

        if ($type === TransactionType::Transfer) {
            $destinationAccount = $workspace->accounts()->whereKey($validated['destination_account_id'])->firstOrFail();
            $feeCategory = isset($validated['fee_category_id'])
                ? $workspace->categories()->whereKey($validated['fee_category_id'])->firstOrFail()
                : null;

            $recordTransfer->record(
                $account,
                $destinationAccount,
                (int) $validated['amount'],
                (int) ($validated['fee_amount'] ?? 0),
                $feeCategory,
                $validated['description'],
                $occurredAt,
                $user,
                $validated['memo'] ?? null,
                $tags,
            );
        } else {
            $category = $workspace->categories()->whereKey($validated['category_id'])->firstOrFail();

            $recordIncomeExpense->record(
                $account,
                $category,
                $type,
                (int) $validated['amount'],
                $validated['description'],
                $occurredAt,
                $user,
                $merchant,
                $validated['memo'] ?? null,
                $tags,
            );
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Transaction recorded.')]);

        return to_route('accounts.index');
    }
}
