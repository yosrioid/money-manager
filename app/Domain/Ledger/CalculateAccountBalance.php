<?php

namespace App\Domain\Ledger;

use App\Models\Account;

class CalculateAccountBalance
{
    public function calculate(Account $account): int
    {
        return (int) $account->ledgerEntries()->sum('amount');
    }
}
