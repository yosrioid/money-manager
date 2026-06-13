<?php

namespace App\Http\Controllers;

use App\Domain\Ledger\CalculateAccountBalance;
use App\Domain\Transactions\DuplicateTransaction;
use App\Domain\Transactions\EvaluateAmountExpression;
use App\Domain\Transactions\RecordIncomeExpense;
use App\Domain\Transactions\RecordTransfer;
use App\Domain\Transactions\SaveTransactionDraft;
use App\Domain\Transactions\SummarizeTransactionPeriod;
use App\Domain\Workspaces\WorkspaceContext;
use App\Enums\LedgerEntryType;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Http\Requests\StoreTransactionDraftRequest;
use App\Http\Requests\StoreTransactionRequest;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\Merchant;
use App\Models\Transaction;
use App\Models\TransactionEntry;
use App\Models\User;
use App\Models\Workspace;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function __construct(
        private readonly WorkspaceContext $workspaceContext,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Transaction::class);

        $workspace = $this->workspaceContext->get();

        $search = trim((string) $request->query('q', ''));

        $type = $request->query('type');
        $type = is_string($type) && TransactionType::tryFrom($type) !== null ? $type : null;

        $status = $request->query('status');
        $status = is_string($status) && TransactionStatus::tryFrom($status) !== null ? $status : null;

        $categoryId = $request->query('category_id');
        $categoryId = is_numeric($categoryId) ? (int) $categoryId : null;

        $accountId = $request->query('account_id');
        $accountId = is_numeric($accountId) ? (int) $accountId : null;

        $tagId = $request->query('tag_id');
        $tagId = is_numeric($tagId) ? (int) $tagId : null;

        $from = $request->query('from');
        $from = is_string($from) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)
            ? Carbon::parse($from, $workspace->timezone)->startOfDay()
            : null;

        $to = $request->query('to');
        $to = is_string($to) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)
            ? Carbon::parse($to, $workspace->timezone)->startOfDay()
            : null;

        $sortOptions = ['date_desc', 'date_asc', 'amount_desc', 'amount_asc', 'description_asc', 'description_desc'];
        $sort = $request->query('sort');
        $sort = is_string($sort) && in_array($sort, $sortOptions, true) ? $sort : 'date_desc';

        $transactions = $workspace->transactions()
            ->whereNotNull('posted_at')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('description', 'like', "%{$search}%")
                        ->orWhere('memo', 'like', "%{$search}%")
                        ->orWhereHas('merchant', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('entries.account', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('entries.category', fn ($q) => $q->where('name', 'like', "%{$search}%"));

                    if (is_numeric($search)) {
                        $amount = (int) round(abs((float) $search));
                        $query->orWhereHas('entries', fn ($q) => $q->whereRaw('abs(amount) = ?', [$amount]));
                    }
                });
            })
            ->when($type !== null, fn ($query) => $query->where('type', $type))
            ->when($status !== null, fn ($query) => $query->where('status', $status))
            ->when($categoryId !== null, fn ($query) => $query->whereHas('entries', fn ($q) => $q->where('category_id', $categoryId)))
            ->when($accountId !== null, fn ($query) => $query->whereHas('entries', fn ($q) => $q->where('account_id', $accountId)))
            ->when($tagId !== null, fn ($query) => $query->whereHas('tags', fn ($q) => $q->where('tags.id', $tagId)))
            ->when($from !== null, fn ($query) => $query->where('occurred_at', '>=', $from->copy()->utc()))
            ->when($to !== null, fn ($query) => $query->where('occurred_at', '<', $to->copy()->addDay()->utc()))
            ->with(['merchant', 'tags', 'entries.account', 'entries.category'])
            ->when(in_array($sort, ['amount_desc', 'amount_asc'], true), function ($query) use ($sort): void {
                $query->addSelect([
                    'account_amount' => TransactionEntry::query()
                        ->selectRaw('max(abs(amount))')
                        ->whereColumn('transaction_id', 'transactions.id')
                        ->where('type', LedgerEntryType::Account),
                ])->orderBy('account_amount', $sort === 'amount_desc' ? 'desc' : 'asc');
            })
            ->when($sort === 'description_asc', fn ($query) => $query->orderBy('description'))
            ->when($sort === 'description_desc', fn ($query) => $query->orderByDesc('description'))
            ->orderBy('occurred_at', $sort === 'date_asc' ? 'asc' : 'desc')
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        $transactions->getCollection()->transform(
            fn (Transaction $transaction): array => $this->transformTransaction($transaction, $workspace)
        );

        return Inertia::render('transactions/Index', [
            'transactions' => Inertia::scroll($transactions),
            'search' => $search,
            'sort' => $sort,
            'sortOptions' => $sortOptions,
            'filters' => [
                'type' => $type,
                'status' => $status,
                'category_id' => $categoryId,
                'account_id' => $accountId,
                'tag_id' => $tagId,
                'from' => $from?->toDateString(),
                'to' => $to?->toDateString(),
            ],
            'filterOptions' => [
                'types' => array_map(fn (TransactionType $type): string => $type->value, TransactionType::cases()),
                'statuses' => [TransactionStatus::Posted->value, TransactionStatus::Reversed->value, TransactionStatus::Replaced->value],
                'categories' => $workspace->categories()->active()->orderBy('name')->get(['id', 'name']),
                'accounts' => $workspace->accounts()->active()->orderBy('name')->get(['id', 'name']),
                'tags' => $workspace->tags()->active()->orderBy('name')->get(['id', 'name', 'color']),
            ],
        ]);
    }

    public function calendar(Request $request, SummarizeTransactionPeriod $summarizeTransactionPeriod): Response
    {
        $this->authorize('viewAny', Transaction::class);

        $workspace = $this->workspaceContext->get();

        $month = $request->query('month');
        $month = is_string($month) && preg_match('/^\d{4}-\d{2}$/', $month)
            ? Carbon::parse($month.'-01', $workspace->timezone)
            : Carbon::now($workspace->timezone);

        $month = $month->startOfMonth();

        $days = $summarizeTransactionPeriod->forMonth($workspace, $month);

        $notes = $workspace->dayNotes()
            ->whereBetween('date', [$month->toDateString(), $month->copy()->endOfMonth()->toDateString()])
            ->pluck('note', 'date')
            ->all();

        return Inertia::render('transactions/Calendar', [
            'month' => $month->toDateString(),
            'days' => $days,
            'notes' => $notes,
            'previousMonth' => $month->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $month->copy()->addMonth()->format('Y-m'),
        ]);
    }

    public function weekly(Request $request, SummarizeTransactionPeriod $summarizeTransactionPeriod): Response
    {
        $this->authorize('viewAny', Transaction::class);

        $workspace = $this->workspaceContext->get();

        $reference = $this->parseLocalDate($request->query('week'), $workspace);
        $weekStart = $reference->copy()->subDays($reference->dayOfWeek)->startOfDay();

        $days = $summarizeTransactionPeriod->forWeek($workspace, $weekStart);

        $totals = ['income' => [], 'expense' => [], 'net' => []];

        foreach ($days as $day) {
            foreach (['income', 'expense', 'net'] as $key) {
                foreach ($day[$key] as $currency => $amount) {
                    $totals[$key][$currency] = ($totals[$key][$currency] ?? 0) + $amount;
                }
            }
        }

        return Inertia::render('transactions/Weekly', [
            'weekStart' => $weekStart->toDateString(),
            'days' => $days,
            'totals' => $totals,
            'previousWeek' => $weekStart->copy()->subDays(7)->toDateString(),
            'nextWeek' => $weekStart->copy()->addDays(7)->toDateString(),
        ]);
    }

    public function monthly(Request $request, SummarizeTransactionPeriod $summarizeTransactionPeriod): Response
    {
        $this->authorize('viewAny', Transaction::class);

        $workspace = $this->workspaceContext->get();

        $year = $request->query('year');
        $year = is_string($year) && preg_match('/^\d{4}$/', $year)
            ? Carbon::createFromDate((int) $year, 1, 1, $workspace->timezone)
            : Carbon::now($workspace->timezone);

        $year = $year->startOfYear();

        $months = $summarizeTransactionPeriod->forYear($workspace, $year);

        return Inertia::render('transactions/Monthly', [
            'year' => $year->format('Y'),
            'months' => $months,
            'previousYear' => $year->copy()->subYear()->format('Y'),
            'nextYear' => $year->copy()->addYear()->format('Y'),
        ]);
    }

    public function summary(Request $request, SummarizeTransactionPeriod $summarizeTransactionPeriod, CalculateAccountBalance $calculateAccountBalance): Response
    {
        $this->authorize('viewAny', Transaction::class);

        $workspace = $this->workspaceContext->get();

        $month = $request->query('month');
        $month = is_string($month) && preg_match('/^\d{4}-\d{2}$/', $month)
            ? Carbon::parse($month.'-01', $workspace->timezone)
            : Carbon::now($workspace->timezone);

        $month = $month->startOfMonth();

        $start = $month->copy()->startOfDay();
        $end = $start->copy()->addMonth();

        $days = $summarizeTransactionPeriod->forMonth($workspace, $month);

        $totals = ['income' => [], 'expense' => [], 'net' => []];
        $count = 0;

        foreach ($days as $day) {
            $count += $day['count'];

            foreach (['income', 'expense', 'net'] as $key) {
                foreach ($day[$key] as $currency => $amount) {
                    $totals[$key][$currency] = ($totals[$key][$currency] ?? 0) + $amount;
                }
            }
        }

        $accountMovements = $workspace->accounts()->active()->orderBy('name')->get()
            ->map(function (Account $account) use ($calculateAccountBalance, $start, $end): array {
                $opening = $calculateAccountBalance->calculateAsOf($account, $start->copy()->utc()->subSecond());
                $closing = $calculateAccountBalance->calculateAsOf($account, $end->copy()->utc()->subSecond());

                return [
                    'id' => $account->id,
                    'name' => $account->name,
                    'currency_code' => $account->currency_code,
                    'opening_balance' => $opening,
                    'closing_balance' => $closing,
                    'change' => $closing - $opening,
                ];
            })->all();

        return Inertia::render('transactions/Summary', [
            'month' => $month->toDateString(),
            'count' => $count,
            'totals' => $totals,
            'accountMovements' => $accountMovements,
            'previousMonth' => $month->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $month->copy()->addMonth()->format('Y-m'),
        ]);
    }

    public function day(Request $request): Response
    {
        $this->authorize('viewAny', Transaction::class);

        $workspace = $this->workspaceContext->get();

        $date = $this->parseLocalDate($request->query('date'), $workspace);

        $start = $date->copy()->startOfDay();
        $end = $start->copy()->addDay();

        $transactions = $workspace->transactions()
            ->whereNotNull('posted_at')
            ->where('occurred_at', '>=', $start->copy()->utc())
            ->where('occurred_at', '<', $end->copy()->utc())
            ->with(['merchant', 'tags', 'entries.account', 'entries.category'])
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Transaction $transaction): array => $this->transformTransaction($transaction, $workspace))
            ->all();

        $note = $workspace->dayNotes()->where('date', $date->toDateString())->first();

        return Inertia::render('transactions/Day', [
            'date' => $date->toDateString(),
            'transactions' => $transactions,
            'note' => $note?->note,
            'previousDate' => $date->copy()->subDay()->toDateString(),
            'nextDate' => $date->copy()->addDay()->toDateString(),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('transactions/CreateTransaction', $this->formProps($request));
    }

    public function editDraft(Request $request, Transaction $transaction): Response
    {
        $this->assertDraftBelongsToCurrentWorkspace($transaction);
        $this->authorize('view', $transaction);

        return Inertia::render('transactions/CreateTransaction', $this->formProps($request, $transaction));
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

    public function bulkDuplicate(Request $request, DuplicateTransaction $duplicateTransaction): RedirectResponse
    {
        $workspace = $this->workspaceContext->get();
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        $validated = $request->validate([
            'transaction_ids' => ['required', 'array', 'min:1'],
            'transaction_ids.*' => ['integer'],
        ]);

        $transactions = $workspace->transactions()->whereIn('id', $validated['transaction_ids'])->get();

        foreach ($transactions as $transaction) {
            $this->authorize('view', $transaction);
        }

        if ($transactions->isEmpty()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('No transactions were selected.')]);

            return to_route('transactions.index');
        }

        $drafts = $transactions->map(fn (Transaction $transaction): Transaction => $duplicateTransaction->duplicate($transaction, $user));

        if ($drafts->count() === 1) {
            Inertia::flash('toast', ['type' => 'success', 'message' => __('Transaction duplicated as a draft.')]);

            return to_route('transactions.drafts.edit', $drafts->first());
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':count transactions duplicated as drafts.', ['count' => $drafts->count()])]);

        return to_route('transactions.drafts.index');
    }

    public function drafts(): Response
    {
        $workspace = $this->workspaceContext->get();

        $drafts = $workspace->transactions()
            ->where('status', TransactionStatus::Draft)
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get();

        return Inertia::render('transactions/Drafts', [
            'drafts' => $drafts->map(fn (Transaction $draft): array => [
                'id' => $draft->id,
                'type' => $draft->type,
                'description' => $draft->description,
                'updated_at' => $draft->updated_at?->toIso8601String(),
            ])->all(),
        ]);
    }

    public function show(Transaction $transaction): Response
    {
        abort_unless($transaction->workspace_id === $this->workspaceContext->get()->id, 404);
        $this->authorize('view', $transaction);

        $workspace = $this->workspaceContext->get();

        $transaction->load([
            'creator',
            'merchant',
            'tags',
            'entries.account',
            'entries.category',
            'reverses',
            'reversal',
            'replaces',
            'replacement',
        ]);

        $auditLogs = AuditLog::query()
            ->where('subject_type', Transaction::class)
            ->where('subject_id', $transaction->id)
            ->with('actor')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $postedAt = $transaction->getRawOriginal('posted_at');

        return Inertia::render('transactions/Show', [
            'transaction' => [
                ...$this->transformTransaction($transaction, $workspace),
                'currency_code' => $transaction->currency_code,
                'posted_at' => $postedAt !== null ? Carbon::parse($postedAt)->toIso8601String() : null,
                'creator' => $transaction->creator?->only(['id', 'name']),
                'entries' => $transaction->entries->map(fn (TransactionEntry $entry): array => [
                    'id' => $entry->id,
                    'type' => $entry->type,
                    'amount' => $entry->amount,
                    'currency_code' => $entry->currency_code,
                    'account' => $entry->account?->only(['id', 'name']),
                    'category' => $entry->category?->only(['id', 'name']),
                ])->all(),
                'reverses' => $transaction->reverses?->only(['id', 'description']),
                'reversal' => $transaction->reversal?->only(['id', 'description']),
                'replaces' => $transaction->replaces?->only(['id', 'description']),
                'replacement' => $transaction->replacement?->only(['id', 'description']),
            ],
            'auditLogs' => $auditLogs->map(fn (AuditLog $log): array => [
                'id' => $log->id,
                'action' => $log->action,
                'actor' => $log->actor?->only(['id', 'name']),
                'created_at' => $log->created_at->toIso8601String(),
                'metadata' => $log->metadata,
            ])->all(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formProps(Request $request, ?Transaction $draft = null): array
    {
        $workspace = $this->workspaceContext->get();

        $bookmarkId = $request->query('bookmark_id');
        $bookmark = is_numeric($bookmarkId)
            ? $workspace->transactionBookmarks()->find((int) $bookmarkId)
            : null;

        $recentTransactions = $workspace->transactions()
            ->whereNotNull('posted_at')
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->limit(50)
            ->get(['description', 'merchant_id']);

        $recentDescriptions = $recentTransactions->pluck('description')
            ->filter(fn (?string $description): bool => filled($description))
            ->unique()
            ->take(8)
            ->values();

        $recentMerchantIds = $recentTransactions->pluck('merchant_id')
            ->filter()
            ->unique()
            ->take(8)
            ->values();

        $recentMerchants = $workspace->merchants()
            ->whereIn('id', $recentMerchantIds)
            ->get(['id', 'name'])
            ->sortBy(fn (Merchant $merchant): int => $recentMerchantIds->search($merchant->id))
            ->values();

        return [
            'accounts' => $workspace->accounts()->active()->orderByDesc('is_favorite')->orderBy('name')->get(['id', 'name', 'currency_code', 'is_favorite']),
            'categories' => $workspace->categories()->active()->orderByDesc('is_favorite')->orderBy('name')->get(['id', 'name', 'type', 'is_favorite']),
            'merchants' => $workspace->merchants()->active()->orderBy('name')->get(['id', 'name']),
            'tags' => $workspace->tags()->active()->orderBy('name')->get(['id', 'name', 'color']),
            'timezone' => $workspace->timezone,
            'idempotencyKey' => (string) Str::uuid(),
            'draftId' => $draft?->id,
            'initialData' => $draft instanceof Transaction ? $draft->draft_data : $bookmark?->payload,
            'bookmarks' => $workspace->transactionBookmarks()
                ->orderBy('position')
                ->get(['id', 'name', 'payload']),
            'recentDescriptions' => $recentDescriptions,
            'recentMerchants' => $recentMerchants,
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

    /**
     * @return array<string, mixed>
     */
    private function transformTransaction(Transaction $transaction, Workspace $workspace): array
    {
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
    }

    /**
     * Parse a workspace-local date query parameter, defaulting to the current
     * workspace-local date when missing or invalid.
     */
    private function parseLocalDate(mixed $value, Workspace $workspace): Carbon
    {
        if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return Carbon::parse($value, $workspace->timezone)->startOfDay();
        }

        return Carbon::now($workspace->timezone)->startOfDay();
    }
}
