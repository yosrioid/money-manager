<?php

namespace App\Http\Controllers;

use App\Domain\Transactions\DuplicateTransaction;
use App\Domain\Transactions\EvaluateAmountExpression;
use App\Domain\Transactions\RecordIncomeExpense;
use App\Domain\Transactions\RecordTransfer;
use App\Domain\Transactions\SaveTransactionDraft;
use App\Domain\Workspaces\WorkspaceContext;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Http\Requests\StoreTransactionDraftRequest;
use App\Http\Requests\StoreTransactionRequest;
use App\Models\Transaction;
use App\Models\TransactionEntry;
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

    public function index(): Response
    {
        $this->authorize('viewAny', Transaction::class);

        $workspace = $this->workspaceContext->get();

        $transactions = $workspace->transactions()
            ->whereNotNull('posted_at')
            ->with(['merchant', 'tags', 'entries.account', 'entries.category'])
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        $transactions->getCollection()->transform(function (Transaction $transaction) use ($workspace): array {
            $occurredAt = Carbon::parse($transaction->getRawOriginal('occurred_at'));

            return [
                'id' => $transaction->id,
                'type' => $transaction->type,
                'status' => $transaction->status,
                'description' => $transaction->description,
                'memo' => $transaction->memo,
                'occurred_at' => $occurredAt->toIso8601String(),
                'local_date' => $occurredAt->setTimezone($workspace->timezone)->toDateString(),
                'merchant' => $transaction->merchant?->only(['id', 'name']),
                'tags' => $transaction->tags->map->only(['id', 'name', 'color'])->all(),
                'entries' => $transaction->entries->map(fn (TransactionEntry $entry): array => [
                    'type' => $entry->type,
                    'amount' => $entry->amount,
                    'currency_code' => $entry->currency_code,
                    'account' => $entry->account?->only(['id', 'name']),
                    'category' => $entry->category?->only(['id', 'name']),
                ])->all(),
            ];
        });

        return Inertia::render('transactions/Index', [
            'transactions' => $transactions,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('transactions/CreateTransaction', $this->formProps());
    }

    public function editDraft(Transaction $transaction): Response
    {
        $this->assertDraftBelongsToCurrentWorkspace($transaction);
        $this->authorize('view', $transaction);

        return Inertia::render('transactions/CreateTransaction', $this->formProps($transaction));
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

        if (isset($validated['draft_id'])) {
            $draft = $workspace->transactions()
                ->whereKey($validated['draft_id'])
                ->where('status', TransactionStatus::Draft)
                ->firstOrFail();
            $this->authorize('update', $draft);
            $draft->update(['status' => TransactionStatus::Voided]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Transaction recorded.')]);

        return to_route('accounts.index');
    }

    public function storeDraft(StoreTransactionDraftRequest $request, SaveTransactionDraft $saveTransactionDraft): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        $draft = $saveTransactionDraft->save($this->workspaceContext->get(), $user, $request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Transaction draft saved.')]);

        return to_route('transactions.drafts.edit', $draft);
    }

    public function updateDraft(StoreTransactionDraftRequest $request, Transaction $transaction, SaveTransactionDraft $saveTransactionDraft): RedirectResponse
    {
        $this->assertDraftBelongsToCurrentWorkspace($transaction);
        $this->authorize('update', $transaction);
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        $saveTransactionDraft->save($this->workspaceContext->get(), $user, $request->validated(), $transaction);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Transaction draft updated.')]);

        return to_route('transactions.drafts.edit', $transaction);
    }

    public function duplicate(Transaction $transaction, DuplicateTransaction $duplicateTransaction): RedirectResponse
    {
        abort_unless($transaction->workspace_id === $this->workspaceContext->get()->id, 404);
        $this->authorize('view', $transaction);
        $user = request()->user();

        abort_unless($user instanceof User, 401);

        $draft = $duplicateTransaction->duplicate($transaction, $user);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Transaction duplicated as a draft.')]);

        return to_route('transactions.drafts.edit', $draft);
    }

    /**
     * @return array<string, mixed>
     */
    private function formProps(?Transaction $draft = null): array
    {
        $workspace = $this->workspaceContext->get();

        return [
            'accounts' => $workspace->accounts()->active()->orderBy('name')->get(['id', 'name', 'currency_code']),
            'categories' => $workspace->categories()->active()->orderBy('name')->get(['id', 'name', 'type']),
            'merchants' => $workspace->merchants()->active()->orderBy('name')->get(['id', 'name']),
            'tags' => $workspace->tags()->active()->orderBy('name')->get(['id', 'name', 'color']),
            'timezone' => $workspace->timezone,
            'idempotencyKey' => (string) Str::uuid(),
            'draftId' => $draft?->id,
            'initialData' => $draft?->draft_data,
        ];
    }

    private function assertDraftBelongsToCurrentWorkspace(Transaction $transaction): void
    {
        abort_unless(
            $transaction->workspace_id === $this->workspaceContext->get()->id
            && $transaction->getRawOriginal('status') === TransactionStatus::Draft->value,
            404,
        );
    }
}
