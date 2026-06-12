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
        if ($account->workspace_id !== $category->workspace_id) {
            throw new LogicException('The account and category must belong to the same workspace.');
        }

        if ($amount < 1) {
            throw new LogicException('The transaction amount must be positive.');
        }

        $expectedCategoryType = $type === TransactionType::Income ? CategoryType::Income : CategoryType::Expense;

        if (! in_array($type, [TransactionType::Income, TransactionType::Expense], true) || $category->getRawOriginal('type') !== $expectedCategoryType->value) {
            throw new LogicException('The category type must match the transaction type.');
        }

        $sign = $type === TransactionType::Income ? 1 : -1;

        return $this->postTransaction->post(
            $account->workspace,
            $type,
            $account->currency_code,
            $description,
            $occurredAt,
            [
                ['account_id' => $account->id, 'category_id' => null, 'type' => LedgerEntryType::Account, 'amount' => $sign * $amount],
                ['account_id' => null, 'category_id' => $category->id, 'type' => LedgerEntryType::Category, 'amount' => -$sign * $amount],
            ],
            $actor,
            $merchant,
            $memo,
            $tags,
        );
    }
}
