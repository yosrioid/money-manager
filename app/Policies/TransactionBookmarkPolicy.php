<?php

namespace App\Policies;

use App\Models\TransactionBookmark;
use App\Models\User;

class TransactionBookmarkPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, TransactionBookmark $transactionBookmark): bool
    {
        return $this->belongsToUserWorkspace($user, $transactionBookmark);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, TransactionBookmark $transactionBookmark): bool
    {
        return $this->belongsToUserWorkspace($user, $transactionBookmark);
    }

    public function delete(User $user, TransactionBookmark $transactionBookmark): bool
    {
        return $this->belongsToUserWorkspace($user, $transactionBookmark);
    }

    private function belongsToUserWorkspace(User $user, TransactionBookmark $transactionBookmark): bool
    {
        return $transactionBookmark->workspace->memberships()
            ->where('user_id', $user->id)
            ->exists();
    }
}
