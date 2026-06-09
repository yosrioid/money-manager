<?php

namespace App\Domain\Workspaces;

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

class CreatePersonalWorkspace
{
    public function create(User $user): Workspace
    {
        return DB::transaction(function () use ($user): Workspace {
            $workspace = $user->ownedWorkspaces()->create([
                'name' => "{$user->name}'s Workspace",
            ]);

            $workspace->memberships()->create([
                'user_id' => $user->id,
                'role' => WorkspaceRole::Owner,
            ]);

            $user->forceFill([
                'current_workspace_id' => $workspace->id,
            ])->save();

            return $workspace;
        });
    }
}
