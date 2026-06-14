<?php

namespace App\Domain\Accounts;

use App\Domain\Ledger\CalculateAccountBalance;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\TransactionEntry;
use Illuminate\Database\Eloquent\Builder;

class CalculateDebtPayoffProgress
{
    public function __construct(
        private readonly CalculateAccountBalance $calculateAccountBalance,
    ) {}

    /**
     * Calculate how much of a loan account's original principal has been
     * repaid, without altering the ledger.
     *
     * @return array{original_principal: int, outstanding: int, paid_amount: int, paid_percentage: float}|null
     */
    public function calculate(Account $account): ?array
    {
        $openingEntry = $account->ledgerEntries()
            ->whereHas('transaction', fn (Builder $query): Builder => $query->where('type', TransactionType::OpeningBalance))
            ->first();

        if (! $openingEntry instanceof TransactionEntry || $openingEntry->amount >= 0) {
            return null;
        }

        $originalPrincipal = -$openingEntry->amount;
        $balance = $this->calculateAccountBalance->calculate($account);
        $outstanding = $balance < 0 ? -$balance : 0;
        $paidAmount = $originalPrincipal - $outstanding;

        return [
            'original_principal' => $originalPrincipal,
            'outstanding' => $outstanding,
            'paid_amount' => $paidAmount,
            'paid_percentage' => round($paidAmount / $originalPrincipal * 100, 2),
        ];
    }
}
