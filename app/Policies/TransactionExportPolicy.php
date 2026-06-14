<?php

namespace App\Policies;

use App\Models\TransactionExport;
use App\Models\User;

class TransactionExportPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, TransactionExport $transactionExport): bool
    {
        return $this->belongsToUserWorkspace($user, $transactionExport);
    }

    public function create(User $user): bool
    {
        return true;
    }

    private function belongsToUserWorkspace(User $user, TransactionExport $transactionExport): bool
    {
        return $transactionExport->workspace->memberships()
            ->where('user_id', $user->id)
            ->exists();
    }
}
