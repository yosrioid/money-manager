<?php

namespace App\Policies;

use App\Models\Account;
use App\Models\User;

class AccountPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Account $account): bool
    {
        return $this->belongsToUserWorkspace($user, $account);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Account $account): bool
    {
        return $this->belongsToUserWorkspace($user, $account);
    }

    public function delete(User $user, Account $account): bool
    {
        return $this->belongsToUserWorkspace($user, $account);
    }

    private function belongsToUserWorkspace(User $user, Account $account): bool
    {
        return $account->workspace->memberships()
            ->where('user_id', $user->id)
            ->exists();
    }
}
