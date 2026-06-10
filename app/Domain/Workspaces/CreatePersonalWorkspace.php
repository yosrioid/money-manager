<?php

namespace App\Domain\Workspaces;

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

class CreatePersonalWorkspace
{
    public function __construct(
        private readonly ApplyStarterPresets $applyStarterPresets,
    ) {}

    public function create(User $user, bool $withStarterPresets = false): Workspace
    {
        return DB::transaction(function () use ($user, $withStarterPresets): Workspace {
            $workspace = $user->ownedWorkspaces()->create([
                'name' => "{$user->name}'s Workspace",
                'default_currency' => 'IDR',
                'timezone' => 'Asia/Jakarta',
                'locale' => 'id',
                'number_format' => 'id-ID',
                'first_day_of_week' => 1,
                'month_start_day' => 1,
                'adjust_month_for_weekend' => false,
                'application_lock_minutes' => 0,
            ]);

            $workspace->memberships()->create([
                'user_id' => $user->id,
                'role' => WorkspaceRole::Owner,
            ]);

            $user->forceFill([
                'current_workspace_id' => $workspace->id,
            ])->save();

            if ($withStarterPresets) {
                $this->applyStarterPresets->apply($workspace);
            }

            return $workspace;
        });
    }
}
