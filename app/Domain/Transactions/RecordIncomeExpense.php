<?php

namespace App\Domain\Transactions;

use App\Domain\Ledger\PostTransaction;
use App\Enums\CategoryType;
use App\Enums\LedgerEntryType;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Merchant;
use App\Models\Tag;
use App\Models\Transaction;
use App\Models\User;
use Carbon\CarbonInterface;
use LogicException;

class RecordIncomeExpense
{
    public function __construct(
        private readonly PostTransaction $postTransaction,
    ) {}

    /**
     * @param  array<int, Tag>  $tags
     */
    public function record(Account $account, Category $category, TransactionType $type, int $amount, string $description, CarbonInterface $occurredAt, User $actor, ?Merchant $merchant = null, ?string $memo = null, array $tags = []): Transaction
    {
        return $this->recordSplit($account, [['category' => $category, 'amount' => $amount]], $type, $description, $occurredAt, $actor, $merchant, $memo, $tags);
    }

    /**
     * @param  array<int, array{category: Category, amount: int}>  $splits
     * @param  array<int, Tag>  $tags
     */
    public function recordSplit(Account $account, array $splits, TransactionType $type, string $description, CarbonInterface $occurredAt, User $actor, ?Merchant $merchant = null, ?string $memo = null, array $tags = []): Transaction
    {
        $expectedCategoryType = $type === TransactionType::Income ? CategoryType::Income : CategoryType::Expense;

        if ($splits === [] || ! in_array($type, [TransactionType::Income, TransactionType::Expense], true)) {
            throw new LogicException('The category type must match the transaction type.');
        }

        foreach ($splits as $split) {
            if ($split['amount'] < 1 || $account->workspace_id !== $split['category']->workspace_id || $split['category']->getRawOriginal('type') !== $expectedCategoryType->value) {
                throw new LogicException('Every split category and amount must match the transaction.');
            }
        }

        $amount = array_sum(array_column($splits, 'amount'));
        $sign = $type === TransactionType::Income ? 1 : -1;
        $entries = [
            ['account_id' => $account->id, 'category_id' => null, 'type' => LedgerEntryType::Account, 'amount' => $sign * $amount],
            ...array_map(fn (array $split): array => [
                'account_id' => null,
                'category_id' => $split['category']->id,
                'type' => LedgerEntryType::Category,
                'amount' => -$sign * $split['amount'],
            ], $splits),
        ];

        return $this->postTransaction->post(
            $account->workspace,
            $type,
            $account->currency_code,
            $description,
            $occurredAt,
            $entries,
            $actor,
            $merchant,
            $memo,
            $tags,
        );
    }
}
