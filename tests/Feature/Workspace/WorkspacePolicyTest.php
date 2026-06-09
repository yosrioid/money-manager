<?php

use App\Domain\Workspaces\CreatePersonalWorkspace;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;

test('an owner can view update and delete its workspace', function () {
    $owner = User::factory()->create();
    $workspace = app(CreatePersonalWorkspace::class)->create($owner);

    expect($owner->can('view', $workspace))->toBeTrue()
        ->and($owner->can('update', $workspace))->toBeTrue()
        ->and($owner->can('delete', $workspace))->toBeTrue();
});

test('a member can view but cannot manage a workspace it does not own', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $workspace = app(CreatePersonalWorkspace::class)->create($owner);

    WorkspaceMember::factory()->create([
        'workspace_id' => $workspace->id,
        'user_id' => $member->id,
    ]);

    expect($member->can('view', $workspace))->toBeTrue()
        ->and($member->can('update', $workspace))->toBeFalse()
        ->and($member->can('delete', $workspace))->toBeFalse();
});

test('an outsider cannot access another workspace', function () {
    $outsider = User::factory()->create();
    $workspace = Workspace::factory()->create();

    expect($outsider->can('view', $workspace))->toBeFalse()
        ->and($outsider->can('update', $workspace))->toBeFalse()
        ->and($outsider->can('delete', $workspace))->toBeFalse();
});
