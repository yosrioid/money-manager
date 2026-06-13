<?php

use App\Domain\Workspaces\CreatePersonalWorkspace;
use App\Models\User;
use Database\Seeders\CurrencySeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(CurrencySeeder::class);
});

function navigationPreferencesPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'My Workspace',
        'default_currency' => 'IDR',
        'timezone' => 'Asia/Jakarta',
        'locale' => 'id',
        'number_format' => 'id-ID',
        'first_day_of_week' => 1,
        'month_start_day' => 1,
        'adjust_month_for_weekend' => false,
        'application_lock_minutes' => 0,
        'navigation_shortcuts_enabled' => true,
    ], $overrides);
}

test('the workspace settings page exposes navigation shortcuts enabled by default', function () {
    $user = User::factory()->create();
    app(CreatePersonalWorkspace::class)->create($user);

    $this->actingAs($user)
        ->get(route('workspace.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/Workspace')
            ->where('workspace.navigation_shortcuts_enabled', true));
});

test('a workspace owner can disable navigation shortcuts', function () {
    $user = User::factory()->create();
    $workspace = app(CreatePersonalWorkspace::class)->create($user);

    $this->actingAs($user)
        ->patch(route('workspace.update'), navigationPreferencesPayload([
            'navigation_shortcuts_enabled' => false,
        ]))
        ->assertRedirect(route('workspace.edit'));

    expect($workspace->fresh()->navigation_shortcuts_enabled)->toBeFalse();
});

test('navigation shortcuts enabled is passed to supported period views', function () {
    $user = User::factory()->create();
    $workspace = app(CreatePersonalWorkspace::class)->create($user);
    $workspace->update(['navigation_shortcuts_enabled' => false]);

    $this->actingAs($user)
        ->get(route('transactions.calendar'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('navigationShortcutsEnabled', false));

    $this->actingAs($user)
        ->get(route('transactions.weekly'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('navigationShortcutsEnabled', false));

    $this->actingAs($user)
        ->get(route('transactions.monthly'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('navigationShortcutsEnabled', false));

    $this->actingAs($user)
        ->get(route('transactions.summary'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('navigationShortcutsEnabled', false));

    $this->actingAs($user)
        ->get(route('transactions.day'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('navigationShortcutsEnabled', false));
});
