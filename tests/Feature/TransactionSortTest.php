<?php

use App\Domain\Transactions\RecordIncomeExpense;
use App\Domain\Workspaces\CreatePersonalWorkspace;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function setUpTransactionSortWorkspace(): array
{
    $user = User::factory()->create();
    $workspace = app(CreatePersonalWorkspace::class)->create($user);
    $account = Account::factory()->for($workspace)->create();
    $incomeCategory = Category::factory()->for($workspace)->income()->create();
    $expenseCategory = Category::factory()->for($workspace)->expense()->create();

    app(RecordIncomeExpense::class)->record($account, $incomeCategory, TransactionType::Income, 50000, 'Bravo', now()->setDate(2026, 6, 10)->setTime(12, 0), $user);
    app(RecordIncomeExpense::class)->record($account, $expenseCategory, TransactionType::Expense, 90000, 'Alpha', now()->setDate(2026, 6, 5)->setTime(12, 0), $user);
    app(RecordIncomeExpense::class)->record($account, $expenseCategory, TransactionType::Expense, 15000, 'Charlie', now()->setDate(2026, 6, 15)->setTime(12, 0), $user);

    return [$user, $workspace, $account, $incomeCategory, $expenseCategory];
}

test('transactions default to newest first', function () {
    [$user] = setUpTransactionSortWorkspace();

    $this->actingAs($user)
        ->get(route('transactions.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transactions/Index')
            ->where('sort', 'date_desc')
            ->where('transactions.data.0.description', 'Charlie')
            ->where('transactions.data.1.description', 'Bravo')
            ->where('transactions.data.2.description', 'Alpha')
        );
});

test('transactions can be sorted by oldest first', function () {
    [$user] = setUpTransactionSortWorkspace();

    $this->actingAs($user)
        ->get(route('transactions.index', ['sort' => 'date_asc']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transactions/Index')
            ->where('sort', 'date_asc')
            ->where('transactions.data.0.description', 'Alpha')
            ->where('transactions.data.1.description', 'Bravo')
            ->where('transactions.data.2.description', 'Charlie')
        );
});

test('transactions can be sorted by amount descending', function () {
    [$user] = setUpTransactionSortWorkspace();

    $this->actingAs($user)
        ->get(route('transactions.index', ['sort' => 'amount_desc']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transactions/Index')
            ->where('sort', 'amount_desc')
            ->where('transactions.data.0.description', 'Alpha')
            ->where('transactions.data.1.description', 'Bravo')
            ->where('transactions.data.2.description', 'Charlie')
        );
});

test('transactions can be sorted by amount ascending', function () {
    [$user] = setUpTransactionSortWorkspace();

    $this->actingAs($user)
        ->get(route('transactions.index', ['sort' => 'amount_asc']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transactions/Index')
            ->where('sort', 'amount_asc')
            ->where('transactions.data.0.description', 'Charlie')
            ->where('transactions.data.1.description', 'Bravo')
            ->where('transactions.data.2.description', 'Alpha')
        );
});

test('transactions can be sorted by description ascending', function () {
    [$user] = setUpTransactionSortWorkspace();

    $this->actingAs($user)
        ->get(route('transactions.index', ['sort' => 'description_asc']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transactions/Index')
            ->where('sort', 'description_asc')
            ->where('transactions.data.0.description', 'Alpha')
            ->where('transactions.data.1.description', 'Bravo')
            ->where('transactions.data.2.description', 'Charlie')
        );
});

test('transactions can be sorted by description descending', function () {
    [$user] = setUpTransactionSortWorkspace();

    $this->actingAs($user)
        ->get(route('transactions.index', ['sort' => 'description_desc']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transactions/Index')
            ->where('sort', 'description_desc')
            ->where('transactions.data.0.description', 'Charlie')
            ->where('transactions.data.1.description', 'Bravo')
            ->where('transactions.data.2.description', 'Alpha')
        );
});

test('an invalid sort value falls back to the default', function () {
    [$user] = setUpTransactionSortWorkspace();

    $this->actingAs($user)
        ->get(route('transactions.index', ['sort' => 'not-a-real-sort']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transactions/Index')
            ->where('sort', 'date_desc')
        );
});
