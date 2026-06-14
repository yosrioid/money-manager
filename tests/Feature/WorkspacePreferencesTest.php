<?php

use App\Domain\Workspaces\CreatePersonalWorkspace;
use App\Models\User;
use Database\Seeders\CurrencySeeder;

beforeEach(function () {
    $this->seed(CurrencySeeder::class);
});

test('workspace settings page is accessible by workspace owner', function () {
    $user = User::factory()->create();
    app(CreatePersonalWorkspace::class)->create($user);

    $this->actingAs($user)
        ->get(route('workspace.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('settings/Workspace'));
});

test('workspace settings page is inaccessible without authentication', function () {
    $this->get(route('workspace.edit'))
        ->assertRedirect(route('login'));
});

test('workspace owner can update workspace name and preferences', function () {
    $user = User::factory()->create();
    $workspace = app(CreatePersonalWorkspace::class)->create($user);

    $this->actingAs($user)
        ->patch(route('workspace.update'), [
            'name' => 'Updated Workspace',
            'default_currency' => 'USD',
            'timezone' => 'America/New_York',
            'locale' => 'en',
            'number_format' => 'en-US',
            'first_day_of_week' => 0,
            'month_start_day' => 1,
            'adjust_month_for_weekend' => false,
            'application_lock_minutes' => 15,
            'navigation_shortcuts_enabled' => true,
        ])
        ->assertRedirect(route('workspace.edit'));

    expect($workspace->fresh())
        ->name->toBe('Updated Workspace')
        ->default_currency->toBe('USD')
        ->timezone->toBe('America/New_York')
        ->locale->toBe('en')
        ->number_format->toBe('en-US')
        ->first_day_of_week->toBe(0)
        ->application_lock_minutes->toBe(15);
});

test('workspace update validates required fields', function () {
    $user = User::factory()->create();
    app(CreatePersonalWorkspace::class)->create($user);

    $this->actingAs($user)
        ->patch(route('workspace.update'), [])
        ->assertSessionHasErrors(['name', 'default_currency', 'timezone', 'locale', 'number_format']);
});

test('workspace update rejects invalid currency code', function () {
    $user = User::factory()->create();
    app(CreatePersonalWorkspace::class)->create($user);

    $this->actingAs($user)
        ->patch(route('workspace.update'), [
            'name' => 'My Workspace',
            'default_currency' => 'XYZ',
            'timezone' => 'Asia/Jakarta',
            'locale' => 'id',
            'number_format' => 'id-ID',
            'first_day_of_week' => 1,
            'month_start_day' => 1,
            'adjust_month_for_weekend' => false,
            'application_lock_minutes' => 0,
            'navigation_shortcuts_enabled' => true,
        ])
        ->assertSessionHasErrors('default_currency');
});

test('workspace update rejects invalid timezone', function () {
    $user = User::factory()->create();
    app(CreatePersonalWorkspace::class)->create($user);

    $this->actingAs($user)
        ->patch(route('workspace.update'), [
            'name' => 'My Workspace',
            'default_currency' => 'IDR',
            'timezone' => 'Not/ATimezone',
            'locale' => 'id',
            'number_format' => 'id-ID',
            'first_day_of_week' => 1,
            'month_start_day' => 1,
            'adjust_month_for_weekend' => false,
            'application_lock_minutes' => 0,
            'navigation_shortcuts_enabled' => true,
        ])
        ->assertSessionHasErrors('timezone');
});

test('workspace preferences are isolated between workspaces', function () {
    $user = User::factory()->create();
    $workspace = app(CreatePersonalWorkspace::class)->create($user);

    $otherUser = User::factory()->create();
    $otherWorkspace = app(CreatePersonalWorkspace::class)->create($otherUser);
    $otherWorkspace->update(['default_currency' => 'EUR']);

    // User updates their workspace; other workspace must remain unchanged
    $this->actingAs($user)
        ->patch(route('workspace.update'), [
            'name' => 'Updated',
            'default_currency' => 'USD',
            'timezone' => 'Asia/Jakarta',
            'locale' => 'id',
            'number_format' => 'id-ID',
            'first_day_of_week' => 1,
            'month_start_day' => 1,
            'adjust_month_for_weekend' => false,
            'application_lock_minutes' => 0,
            'navigation_shortcuts_enabled' => true,
        ]);

    expect($otherWorkspace->fresh()->default_currency)->toBe('EUR');
});

test('workspace owner can set a net asset target', function () {
    $user = User::factory()->create();
    $workspace = app(CreatePersonalWorkspace::class)->create($user);

    $this->actingAs($user)
        ->patch(route('workspace.update'), [
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
            'net_asset_target' => 100000000,
        ])
        ->assertRedirect(route('workspace.edit'));

    expect($workspace->fresh()->net_asset_target)->toBe(100000000);
});

test('workspace update rejects a negative net asset target', function () {
    $user = User::factory()->create();
    app(CreatePersonalWorkspace::class)->create($user);

    $this->actingAs($user)
        ->patch(route('workspace.update'), [
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
            'net_asset_target' => -1,
        ])
        ->assertSessionHasErrors('net_asset_target');
});

test('workspace owner can configure visible report widgets', function () {
    $user = User::factory()->create();
    $workspace = app(CreatePersonalWorkspace::class)->create($user);

    $this->actingAs($user)
        ->patch(route('workspace.update'), [
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
            'report_widgets' => ['summary', 'netWorth'],
        ])
        ->assertRedirect(route('workspace.edit'));

    expect($workspace->fresh()->reportWidgets())->toBe(['summary', 'netWorth']);
});

test('workspace update rejects an unsupported report widget', function () {
    $user = User::factory()->create();
    app(CreatePersonalWorkspace::class)->create($user);

    $this->actingAs($user)
        ->patch(route('workspace.update'), [
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
            'report_widgets' => ['bogus'],
        ])
        ->assertSessionHasErrors('report_widgets.0');
});

test('workspace application lock only accepts supported inactivity periods', function () {
    $user = User::factory()->create();
    app(CreatePersonalWorkspace::class)->create($user);

    $this->actingAs($user)
        ->patch(route('workspace.update'), [
            'name' => 'My Workspace',
            'default_currency' => 'IDR',
            'timezone' => 'Asia/Jakarta',
            'locale' => 'id',
            'number_format' => 'id-ID',
            'first_day_of_week' => 1,
            'month_start_day' => 1,
            'adjust_month_for_weekend' => false,
            'application_lock_minutes' => 7,
            'navigation_shortcuts_enabled' => true,
        ])
        ->assertSessionHasErrors('application_lock_minutes');
});
