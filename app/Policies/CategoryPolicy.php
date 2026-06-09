<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Category $category): bool
    {
        return $this->belongsToUserWorkspace($user, $category);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Category $category): bool
    {
        return $this->belongsToUserWorkspace($user, $category);
    }

    public function delete(User $user, Category $category): bool
    {
        return $this->belongsToUserWorkspace($user, $category)
            && $category->subcategories()->count() === 0;
    }

    private function belongsToUserWorkspace(User $user, Category $category): bool
    {
        return $category->workspace->memberships()
            ->where('user_id', $user->id)
            ->exists();
    }
}
