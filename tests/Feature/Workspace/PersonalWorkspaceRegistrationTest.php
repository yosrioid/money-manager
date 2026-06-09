<?php

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;

test('registration creates a personal workspace and selects it', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $user = User::query()->where('email', 'test@example.com')->sole();
    $workspace = Workspace::query()->sole();
    $membership = WorkspaceMember::query()->sole();

    $response->assertRedirect(route('dashboard', absolute: false));
    $this->assertAuthenticatedAs($user);

    expect($workspace->owner_id)->toBe($user->id)
        ->and($workspace->name)->toBe("Test User's Workspace")
        ->and($membership->workspace_id)->toBe($workspace->id)
        ->and($membership->user_id)->toBe($user->id)
        ->and($membership->role)->toBe(WorkspaceRole::Owner)
        ->and($user->current_workspace_id)->toBe($workspace->id);
});
