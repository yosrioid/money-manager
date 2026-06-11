<?php

namespace App\Domain\Transactions;

use App\Domain\Ledger\PostTransaction;
use App\Enums\LedgerEntryType;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Carbon\CarbonInterface;

class RecordIncomeExpense
{
    public function __construct(
        private readonly PostTransaction $postTransaction,
    ) {}

    public function record(Account $account, Category $category, TransactionType $type, int $amount, string $description, CarbonInterface $occurredAt, User $actor): Transaction
    {
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
        );
    }
}
