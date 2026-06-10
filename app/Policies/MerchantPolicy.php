<?php

namespace App\Policies;

use App\Models\Merchant;
use App\Models\User;

class MerchantPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Merchant $merchant): bool
    {
        return $this->belongsToUserWorkspace($user, $merchant);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Merchant $merchant): bool
    {
        return $this->belongsToUserWorkspace($user, $merchant);
    }

    public function delete(User $user, Merchant $merchant): bool
    {
        return $this->belongsToUserWorkspace($user, $merchant);
    }

    private function belongsToUserWorkspace(User $user, Merchant $merchant): bool
    {
        return $merchant->workspace->memberships()
            ->where('user_id', $user->id)
            ->exists();
    }
}
