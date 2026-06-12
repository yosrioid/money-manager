<?php

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;

class TransactionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Transaction $transaction): bool
    {
        return $this->belongsToUserWorkspace($user, $transaction);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Transaction $transaction): bool
    {
        return $this->belongsToUserWorkspace($user, $transaction);
    }

    public function delete(User $user, Transaction $transaction): bool
    {
        return $this->belongsToUserWorkspace($user, $transaction);
    }

    private function belongsToUserWorkspace(User $user, Transaction $transaction): bool
    {
        return $transaction->workspace->memberships()
            ->where('user_id', $user->id)
            ->exists();
    }
}
