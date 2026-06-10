<?php

namespace App\Policies;

use App\Models\Tag;
use App\Models\User;

class TagPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Tag $tag): bool
    {
        return $this->belongsToUserWorkspace($user, $tag);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Tag $tag): bool
    {
        return $this->belongsToUserWorkspace($user, $tag);
    }

    public function delete(User $user, Tag $tag): bool
    {
        return $this->belongsToUserWorkspace($user, $tag);
    }

    private function belongsToUserWorkspace(User $user, Tag $tag): bool
    {
        return $tag->workspace->memberships()
            ->where('user_id', $user->id)
            ->exists();
    }
}
