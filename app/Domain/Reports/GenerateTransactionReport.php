<?php

namespace App\Domain\Reports;

use App\Domain\Ledger\CalculateAccountBalance;
use App\Enums\CategoryType;
use App\Enums\LedgerEntryType;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\Workspace;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GenerateTransactionReport
{
    public function __construct(
        private readonly CalculateAccountBalance $calculateAccountBalance,
    ) {}

    /**
     * Summarize posted income and expense totals within the given
     * workspace-local period, honoring the given filters.
     *
     * @param  array{account_ids?: array<int, int>, category_ids?: array<int, int>, merchant_id?: ?int, tag_ids?: array<int, int>}  $filters
     * @return array{income: array<string, int>, expense: array<string, int>, net: array<string, int>, count: int}
     */
    public function summary(Workspace $workspace, CarbonInterface $start, CarbonInterface $end, array $filters = []): array
    {
        $transactions = $this->filteredTransactions($workspace, $start, $end, $filters)->get();

        $income = [];
        $expense = [];
        $count = $transactions->count();

        foreach ($transactions as $transaction) {
            $type = $transaction->getRawOriginal('type');

            if (! in_array($type, [TransactionType::Income->value, TransactionType::Expense->value], true)) {
                continue;
            }

            $accountEntry = $transaction->entries->firstWhere('type', LedgerEntryType::Account);

            if ($accountEntry === null) {
                continue;
            }

            $currency = $accountEntry->currency_code;

            if ($accountEntry->amount >= 0) {
                $income[$currency] = ($income[$currency] ?? 0) + $accountEntry->amount;
            } else {
                $expense[$currency] = ($expense[$currency] ?? 0) + abs($accountEntry->amount);
            }
        }

        $net = [];

        foreach (array_unique([...array_keys($income), ...array_keys($expense)]) as $currency) {
            $net[$currency] = ($income[$currency] ?? 0) - ($expense[$currency] ?? 0);
        }

        return ['income' => $income, 'expense' => $expense, 'net' => $net, 'count' => $count];
    }

    /**
     * Compare the given period's summary with the immediately preceding
     * period of the same length, honoring the given filters.
     *
     * @param  array{account_ids?: array<int, int>, category_ids?: array<int, int>, merchant_id?: ?int, tag_ids?: array<int, int>}  $filters
     * @return array{current: array{income: array<string, int>, expense: array<string, int>, net: array<string, int>, count: int}, previous: array{income: array<string, int>, expense: array<string, int>, net: array<string, int>, count: int}}
     */
    public function comparison(Workspace $workspace, CarbonInterface $start, CarbonInterface $end, array $filters = []): array
    {
        $durationInSeconds = $end->diffInSeconds($start);

        $previousEnd = $start->copy();
        $previousStart = $previousEnd->copy()->subSeconds($durationInSeconds);

        return [
            'current' => $this->summary($workspace, $start, $end, $filters),
            'previous' => $this->summary($workspace, $previousStart, $previousEnd, $filters),
        ];
    }

    /**
     * Break down posted category-entry totals by category for the given
     * category type within the given period, honoring the given filters.
     *
     * @param  array{account_ids?: array<int, int>, category_ids?: array<int, int>, merchant_id?: ?int, tag_ids?: array<int, int>}  $filters
     * @return array<int, array{category_id: int, name: string, parent_id: ?int, parent_name: ?string, amount: int, currency: string}>
     */
    public function byCategory(Workspace $workspace, CarbonInterface $start, CarbonInterface $end, CategoryType $type, array $filters = []): array
    {
        $transactions = $this->filteredTransactions($workspace, $start, $end, $filters)
            ->with('entries.category.parent')
            ->get();

        $totals = [];

        foreach ($transactions as $transaction) {
            foreach ($transaction->entries as $entry) {
                $category = $entry->category;

                if ($entry->getRawOriginal('type') !== LedgerEntryType::Category->value || $category === null || $category->getRawOriginal('type') !== $type->value) {
                    continue;
                }

                $totals[$category->id] ??= [
                    'category_id' => $category->id,
                    'name' => $category->name,
                    'parent_id' => $category->parent_id,
                    'parent_name' => $category->parent?->name,
                    'amount' => 0,
                    'currency' => $entry->currency_code,
                ];

                $totals[$category->id]['amount'] += abs($entry->amount);
            }
        }

        $rows = array_values($totals);

        usort($rows, fn (array $a, array $b): int => $b['amount'] <=> $a['amount']);

        return $rows;
    }

    /**
     * Break down posted expense totals by merchant within the given period,
     * honoring the given filters.
     *
     * @param  array{account_ids?: array<int, int>, category_ids?: array<int, int>, merchant_id?: ?int, tag_ids?: array<int, int>}  $filters
     * @return array<int, array{merchant_id: ?int, name: string, amount: int, currency: string}>
     */
    public function byMerchant(Workspace $workspace, CarbonInterface $start, CarbonInterface $end, array $filters = []): array
    {
        $transactions = $this->filteredTransactions($workspace, $start, $end, $filters)
            ->where('type', TransactionType::Expense)
            ->with('merchant')
            ->get();

        $totals = [];

        foreach ($transactions as $transaction) {
            $accountEntry = $transaction->entries->firstWhere('type', LedgerEntryType::Account);

            if ($accountEntry === null) {
                continue;
            }

            $key = $transaction->merchant_id ?? 0;

            $totals[$key] ??= [
                'merchant_id' => $transaction->merchant_id,
                'name' => $transaction->merchant_id === null ? 'No merchant' : $transaction->merchant->name,
                'amount' => 0,
                'currency' => $accountEntry->currency_code,
            ];

            $totals[$key]['amount'] += abs($accountEntry->amount);
        }

        $rows = array_values($totals);

        usort($rows, fn (array $a, array $b): int => $b['amount'] <=> $a['amount']);

        return $rows;
    }

    /**
     * Summarize activity and balance movement per account within the given
     * period, honoring the given filters.
     *
     * @param  array{account_ids?: array<int, int>, category_ids?: array<int, int>, merchant_id?: ?int, tag_ids?: array<int, int>}  $filters
     * @return array<int, array{account_id: int, name: string, currency_code: string, opening_balance: int, closing_balance: int, change: int, income: int, expense: int, count: int}>
     */
    public function byAccount(Workspace $workspace, CarbonInterface $start, CarbonInterface $end, array $filters = []): array
    {
        $accounts = $workspace->accounts()->active()
            ->when(! empty($filters['account_ids']), fn (Builder $query) => $query->whereIn('id', $filters['account_ids']))
            ->orderBy('name')
            ->get();

        $transactions = $this->filteredTransactions($workspace, $start, $end, $filters)->get();

        $activity = [];

        foreach ($transactions as $transaction) {
            foreach ($transaction->entries as $entry) {
                if ($entry->getRawOriginal('type') !== LedgerEntryType::Account->value || $entry->account_id === null) {
                    continue;
                }

                $activity[$entry->account_id] ??= ['income' => 0, 'expense' => 0, 'count' => 0];
                $activity[$entry->account_id]['count']++;

                if ($entry->amount >= 0) {
                    $activity[$entry->account_id]['income'] += $entry->amount;
                } else {
                    $activity[$entry->account_id]['expense'] += abs($entry->amount);
                }
            }
        }

        return $accounts->map(function (Account $account) use ($activity, $start, $end): array {
            $opening = $this->calculateAccountBalance->calculateAsOf($account, $start->copy()->utc()->subSecond());
            $closing = $this->calculateAccountBalance->calculateAsOf($account, $end->copy()->utc()->subSecond());

            $stats = $activity[$account->id] ?? ['income' => 0, 'expense' => 0, 'count' => 0];

            return [
                'account_id' => $account->id,
                'name' => $account->name,
                'currency_code' => $account->currency_code,
                'opening_balance' => $opening,
                'closing_balance' => $closing,
                'change' => $closing - $opening,
                'income' => $stats['income'],
                'expense' => $stats['expense'],
                'count' => $stats['count'],
            ];
        })->all();
    }

    /**
     * @param  array{account_ids?: array<int, int>, category_ids?: array<int, int>, merchant_id?: ?int, tag_ids?: array<int, int>}  $filters
     * @return HasMany<Transaction, Workspace>
     */
    private function filteredTransactions(Workspace $workspace, CarbonInterface $start, CarbonInterface $end, array $filters): HasMany
    {
        $query = $workspace->transactions()
            ->whereNotNull('posted_at')
            ->where('include_in_statistics', true)
            ->where('status', '!=', TransactionStatus::Replaced->value)
            ->where('occurred_at', '>=', $start->copy()->utc())
            ->where('occurred_at', '<', $end->copy()->utc())
            ->with('entries');

        if (! empty($filters['account_ids'])) {
            $query->whereHas('entries', fn (Builder $entries) => $entries->where('type', LedgerEntryType::Account)->whereIn('account_id', $filters['account_ids']));
        }

        if (! empty($filters['category_ids'])) {
            $query->whereHas('entries', fn (Builder $entries) => $entries->where('type', LedgerEntryType::Category)->whereIn('category_id', $filters['category_ids']));
        }

        if (! empty($filters['merchant_id'])) {
            $query->where('merchant_id', $filters['merchant_id']);
        }

        if (! empty($filters['tag_ids'])) {
            $query->whereHas('tags', fn (Builder $tags) => $tags->whereIn('tags.id', $filters['tag_ids']));
        }

        return $query;
    }
}
