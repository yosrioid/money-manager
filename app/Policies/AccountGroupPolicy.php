<?php

namespace App\Policies;

use App\Models\AccountGroup;
use App\Models\User;

class AccountGroupPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, AccountGroup $accountGroup): bool
    {
        return $this->belongsToUserWorkspace($user, $accountGroup);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, AccountGroup $accountGroup): bool
    {
        return $this->belongsToUserWorkspace($user, $accountGroup);
    }

    public function delete(User $user, AccountGroup $accountGroup): bool
    {
        return $this->belongsToUserWorkspace($user, $accountGroup)
            && $accountGroup->accounts()->count() === 0;
    }

    private function belongsToUserWorkspace(User $user, AccountGroup $accountGroup): bool
    {
        return $accountGroup->workspace->memberships()
            ->where('user_id', $user->id)
            ->exists();
    }
}
