<?php

namespace App\Domain\Transactions;

use App\Domain\Ledger\PostTransaction;
use App\Enums\CategoryType;
use App\Enums\LedgerEntryType;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Tag;
use App\Models\Transaction;
use App\Models\User;
use Carbon\CarbonInterface;
use LogicException;

class RecordTransfer
{
    public function __construct(
        private readonly PostTransaction $postTransaction,
    ) {}

    /**
     * @param  array<int, Tag>  $tags
     */
    public function record(Account $sourceAccount, Account $destinationAccount, int $amount, int $feeAmount, ?Category $feeCategory, string $description, CarbonInterface $occurredAt, User $actor, ?string $memo = null, array $tags = [], ?string $idempotencyKey = null, ?int $destinationAmount = null, ?string $exchangeRate = null): Transaction
    {
        if ($amount < 1 || $feeAmount < 0) {
            throw new LogicException('Transfer amounts must be valid positive minor-unit values.');
        }

        if ($sourceAccount->is($destinationAccount)) {
            throw new LogicException('Transfer accounts must be different.');
        }

        if ($sourceAccount->workspace_id !== $destinationAccount->workspace_id) {
            throw new LogicException('Transfer accounts must belong to the same workspace.');
        }

        $crossCurrency = $sourceAccount->currency_code !== $destinationAccount->currency_code;

        if ($crossCurrency) {
            if ($destinationAmount === null || $destinationAmount < 1 || $exchangeRate === null || (float) $exchangeRate <= 0) {
                throw new LogicException('Cross-currency transfers require a positive destination amount and exchange rate.');
            }
        } else {
            $destinationAmount = $amount;
        }

        if ($feeAmount > 0 && (! $feeCategory instanceof Category || $feeCategory->workspace_id !== $sourceAccount->workspace_id || $feeCategory->getRawOriginal('type') !== CategoryType::Expense->value)) {
            throw new LogicException('Transfer fees require an expense category from the same workspace.');
        }

        $entries = [
            ['account_id' => $sourceAccount->id, 'category_id' => null, 'type' => LedgerEntryType::Account, 'amount' => -($amount + $feeAmount), 'currency_code' => $sourceAccount->currency_code, 'base_amount' => -($amount + $feeAmount)],
            ['account_id' => $destinationAccount->id, 'category_id' => null, 'type' => LedgerEntryType::Account, 'amount' => $destinationAmount, 'currency_code' => $destinationAccount->currency_code, 'base_amount' => $amount],
        ];

        if ($feeAmount > 0) {
            $entries[] = ['account_id' => null, 'category_id' => $feeCategory->id, 'type' => LedgerEntryType::Category, 'amount' => $feeAmount, 'currency_code' => $sourceAccount->currency_code, 'base_amount' => $feeAmount];
        }

        return $this->postTransaction->post(
            $sourceAccount->workspace,
            TransactionType::Transfer,
            $sourceAccount->currency_code,
            $description,
            $occurredAt,
            $entries,
            $actor,
            null,
            $memo,
            $tags,
            $idempotencyKey,
            $crossCurrency ? $exchangeRate : null,
        );
    }
}
