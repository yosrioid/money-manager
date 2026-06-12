<?php

namespace App\Policies;

use App\Models\DayNote;
use App\Models\User;

class DayNotePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, DayNote $dayNote): bool
    {
        return $this->belongsToUserWorkspace($user, $dayNote);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, DayNote $dayNote): bool
    {
        return $this->belongsToUserWorkspace($user, $dayNote);
    }

    public function delete(User $user, DayNote $dayNote): bool
    {
        return $this->belongsToUserWorkspace($user, $dayNote);
    }

    private function belongsToUserWorkspace(User $user, DayNote $dayNote): bool
    {
        return $dayNote->workspace->memberships()
            ->where('user_id', $user->id)
            ->exists();
    }
}
