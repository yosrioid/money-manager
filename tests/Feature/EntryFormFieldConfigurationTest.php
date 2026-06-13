<?php

use App\Domain\Workspaces\CreatePersonalWorkspace;
use App\Models\User;
use Database\Seeders\CurrencySeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(CurrencySeeder::class);
});

function basePreferencesPayload(): array
{
    return [
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
    ];
}

test('the transaction entry form defaults to merchant, memo, and tags', function () {
    $user = User::factory()->create();
    app(CreatePersonalWorkspace::class)->create($user);

    $this->actingAs($user)
        ->get(route('transactions.create'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transactions/CreateTransaction')
            ->where('entryFormFields', ['merchant', 'memo', 'tags']));
});

test('the workspace settings page exposes the current entry form field configuration', function () {
    $user = User::factory()->create();
    $workspace = app(CreatePersonalWorkspace::class)->create($user);
    $workspace->update(['entry_form_fields' => ['tags', 'memo']]);

    $this->actingAs($user)
        ->get(route('workspace.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/Workspace')
            ->where('entryFormFields', ['tags', 'memo']));
});

test('a workspace owner can reorder and hide entry form fields', function () {
    $user = User::factory()->create();
    $workspace = app(CreatePersonalWorkspace::class)->create($user);

    $this->actingAs($user)
        ->patch(route('workspace.update'), [
            ...basePreferencesPayload(),
            'entry_form_fields' => ['tags', 'merchant'],
        ])
        ->assertRedirect(route('workspace.edit'));

    expect($workspace->fresh()->entryFormFields())->toBe(['tags', 'merchant']);

    $this->actingAs($user)
        ->get(route('transactions.create'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('entryFormFields', ['tags', 'merchant']));
});

test('hiding all entry form fields stores an empty configuration', function () {
    $user = User::factory()->create();
    $workspace = app(CreatePersonalWorkspace::class)->create($user);
    $workspace->update(['entry_form_fields' => ['merchant', 'memo', 'tags']]);

    $this->actingAs($user)
        ->patch(route('workspace.update'), basePreferencesPayload())
        ->assertRedirect(route('workspace.edit'));

    expect($workspace->fresh()->entryFormFields())->toBe([]);
});

test('entry form fields must be valid field keys without duplicates', function () {
    $user = User::factory()->create();
    app(CreatePersonalWorkspace::class)->create($user);

    $this->actingAs($user)
        ->patch(route('workspace.update'), [
            ...basePreferencesPayload(),
            'entry_form_fields' => ['merchant', 'merchant'],
        ])
        ->assertSessionHasErrors('entry_form_fields.1');

    $this->actingAs($user)
        ->patch(route('workspace.update'), [
            ...basePreferencesPayload(),
            'entry_form_fields' => ['unknown_field'],
        ])
        ->assertSessionHasErrors('entry_form_fields.0');
});
