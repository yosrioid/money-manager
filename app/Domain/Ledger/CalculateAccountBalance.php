<?php

namespace App\Domain\Ledger;

use App\Enums\TransactionStatus;
use App\Models\Account;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

class CalculateAccountBalance
{
    public function calculate(Account $account): int
    {
        return (int) $account->postedLedgerEntries()->sum('amount');
    }

    /**
     * Calculate the account balance as of a given instant.
     *
     * The caller is responsible for resolving any workspace-timezone period
     * boundary into a UTC instant before calling this method.
     */
    public function calculateAsOf(Account $account, CarbonInterface $date): int
    {
        return (int) $account->ledgerEntries()
            ->whereHas('transaction', fn (Builder $query) => $query->where('status', TransactionStatus::Posted)
                ->where('posted_at', '<=', $date))
            ->sum('amount');
    }
}
