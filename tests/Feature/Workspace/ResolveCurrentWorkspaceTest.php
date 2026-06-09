<?php

use App\Domain\Workspaces\CreatePersonalWorkspace;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Inertia\Testing\AssertableInertia as Assert;

test('a member can access the dashboard with its active workspace', function () {
    $user = User::factory()->create();
    $workspace = app(CreatePersonalWorkspace::class)->create($user);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('activeWorkspace.id', $workspace->id)
            ->where('activeWorkspace.name', $workspace->name)
        );
});

test('an active workspace without membership is rejected even for its owner', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create(['owner_id' => $user->id]);

    $user->forceFill(['current_workspace_id' => $workspace->id])->save();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertForbidden();
});

test('a missing active workspace is rejected', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertForbidden();
});

test('a membership grants access even when the user does not own the workspace', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $workspace = app(CreatePersonalWorkspace::class)->create($owner);

    WorkspaceMember::factory()->create([
        'workspace_id' => $workspace->id,
        'user_id' => $member->id,
    ]);
    $member->forceFill(['current_workspace_id' => $workspace->id])->save();

    $this->actingAs($member)
        ->get(route('dashboard'))
        ->assertOk();
});
