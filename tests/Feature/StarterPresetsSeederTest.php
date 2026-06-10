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
    ])->and($expenseCategories->pluck('position')->all())->toBe(range(0, 8));
});
