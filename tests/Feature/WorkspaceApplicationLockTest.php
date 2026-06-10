<?php

use App\Domain\Workspaces\CreatePersonalWorkspace;
use App\Models\User;
use Illuminate\Support\Carbon;

test('workspace application lock redirects after configured inactivity', function () {
    Carbon::setTestNow('2026-06-10 10:00:00');

    $user = User::factory()->create();
    $workspace = app(CreatePersonalWorkspace::class)->create($user);
    $workspace->update(['application_lock_minutes' => 5]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();

    Carbon::setTestNow('2026-06-10 10:05:00');

    $this->get(route('dashboard'))
        ->assertRedirect(route('password.confirm'));
});

test('password confirmation unlocks an inactive workspace session', function () {
    Carbon::setTestNow('2026-06-10 10:00:00');

    $user = User::factory()->create();
    $workspace = app(CreatePersonalWorkspace::class)->create($user);
    $workspace->update(['application_lock_minutes' => 5]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();

    Carbon::setTestNow('2026-06-10 10:06:00');

    $this->get(route('dashboard'))
        ->assertRedirect(route('password.confirm'));

    $this->post(route('password.confirm.store'), ['password' => 'password'])
        ->assertRedirect(route('dashboard'));

    $this->get(route('dashboard'))
        ->assertOk();
});

test('workspace application lock can be disabled', function () {
    Carbon::setTestNow('2026-06-10 10:00:00');

    $user = User::factory()->create();
    app(CreatePersonalWorkspace::class)->create($user);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();

    Carbon::setTestNow('2026-06-11 10:00:00');

    $this->get(route('dashboard'))
        ->assertOk();
});
