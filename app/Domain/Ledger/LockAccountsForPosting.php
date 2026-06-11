<?php

namespace App\Domain\Ledger;

use App\Models\Account;
use Illuminate\Support\Collection;

class LockAccountsForPosting
{
    /**
     * @param  array<int, int|null>  $accountIds
     * @return Collection<int, Account>
     */
    public function lock(array $accountIds): Collection
    {
        $ids = array_values(array_unique(array_filter($accountIds)));

        if ($ids === []) {
            return collect();
        }

        sort($ids);

        return Account::query()->whereIn('id', $ids)->lockForUpdate()->get()->keyBy('id');
    }
}
