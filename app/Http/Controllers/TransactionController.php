<?php

namespace App\Http\Controllers;

use App\Domain\Transactions\DuplicateTransaction;
use App\Domain\Transactions\EvaluateAmountExpression;
use App\Domain\Transactions\RecordIncomeExpense;
use App\Domain\Transactions\RecordTransfer;
use App\Domain\Transactions\SaveTransactionDraft;
use App\Domain\Workspaces\WorkspaceContext;
use App\Enums\TransactionType;
use App\Http\Requests\StoreTransactionDraftRequest;
use App\Http\Requests\StoreTransactionRequest;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
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
            'idempotencyKey' => (string) Str::uuid(),
        ]);
    }

    public function store(StoreTransactionRequest $request, RecordIncomeExpense $recordIncomeExpense, RecordTransfer $recordTransfer, EvaluateAmountExpression $evaluator): RedirectResponse
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
        $amount = $evaluator->evaluate($validated['amount']);

        if ($type === TransactionType::Transfer) {
            $destinationAccount = $workspace->accounts()->whereKey($validated['destination_account_id'])->firstOrFail();
            $feeCategory = isset($validated['fee_category_id'])
                ? $workspace->categories()->whereKey($validated['fee_category_id'])->firstOrFail()
                : null;

            $recordTransfer->record(
                $account,
                $destinationAccount,
                $amount,
                filled($validated['fee_amount'] ?? null) ? $evaluator->evaluate($validated['fee_amount']) : 0,
                $feeCategory,
                $validated['description'],
                $occurredAt,
                $user,
                $validated['memo'] ?? null,
                $tags,
                $validated['idempotency_key'],
            );
        } else {
            if (isset($validated['splits']) && is_array($validated['splits'])) {
                $splits = [];

                foreach ($validated['splits'] as $split) {
                    $splits[] = [
                        'category' => $workspace->categories()->whereKey($split['category_id'])->firstOrFail(),
                        'amount' => $evaluator->evaluate($split['amount']),
                    ];
                }
            } else {
                $splits = [[
                    'category' => $workspace->categories()->whereKey($validated['category_id'])->firstOrFail(),
                    'amount' => $amount,
                ]];
            }

            $recordIncomeExpense->recordSplit(
                $account,
                $splits,
                $type,
                $validated['description'],
                $occurredAt,
                $user,
                $merchant,
                $validated['memo'] ?? null,
                $tags,
                $validated['idempotency_key'],
            );
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Transaction recorded.')]);

        return to_route('accounts.index');
    }

    public function storeDraft(StoreTransactionDraftRequest $request, SaveTransactionDraft $saveTransactionDraft): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        $saveTransactionDraft->save($this->workspaceContext->get(), $user, $request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Transaction draft saved.')]);

        return to_route('accounts.index');
    }

    public function duplicate(Transaction $transaction, DuplicateTransaction $duplicateTransaction): RedirectResponse
    {
        abort_unless($transaction->workspace_id === $this->workspaceContext->get()->id, 404);
        $this->authorize('view', $transaction);
        $user = request()->user();

        abort_unless($user instanceof User, 401);

        $duplicateTransaction->duplicate($transaction, $user);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Transaction duplicated as a draft.')]);

        return to_route('accounts.index');
    }
}
