<?php

use App\Domain\Workspaces\CreatePersonalWorkspace;
use App\Enums\CategoryType;
use App\Models\User;
use Database\Seeders\StarterPresetsSeeder;

test('starter presets seed expense category positions deterministically and idempotently', function () {
    $user = User::factory()->create();
    $workspace = app(CreatePersonalWorkspace::class)->create($user);

    $seeder = app(StarterPresetsSeeder::class);
    $seeder->run($workspace);
    $seeder->run($workspace);

    $expenseCategories = $workspace->categories()
        ->where('type', CategoryType::Expense)
        ->whereNull('parent_id')
        ->orderBy('position')
        ->get(['name', 'position']);

    expect($expenseCategories->pluck('name')->all())->toBe([
        'Food & Drinks',
        'Transportation',
        'Housing',
        'Health',
        'Shopping',
        'Entertainment',
        'Education',
        'Personal Care',
        'Other Expenses',
    ])->and($expenseCategories->pluck('position')->all())->toBe(range(0, 8))
        ->and($workspace->accountGroups()->count())->toBe(3)
        ->and($workspace->accounts()->count())->toBe(4)
        ->and($workspace->categories()->where('type', CategoryType::Income)->count())->toBe(5)
        ->and($workspace->categories()->whereNotNull('parent_id')->count())->toBe(24);
});

test('starter presets remain isolated to the selected workspace', function () {
    $user = User::factory()->create();
    $workspace = app(CreatePersonalWorkspace::class)->create($user);
    $otherWorkspace = app(CreatePersonalWorkspace::class)->create(User::factory()->create());

    app(StarterPresetsSeeder::class)->run($workspace);

    expect($workspace->accounts()->count())->toBe(4)
        ->and($otherWorkspace->accounts()->count())->toBe(0)
        ->and($otherWorkspace->categories()->count())->toBe(0);
});
