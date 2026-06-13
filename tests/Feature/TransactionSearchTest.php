<?php

use App\Domain\Transactions\RecordIncomeExpense;
use App\Domain\Workspaces\CreatePersonalWorkspace;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Merchant;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function setUpTransactionSearchWorkspace(): array
{
    $user = User::factory()->create();
    $workspace = app(CreatePersonalWorkspace::class)->create($user);
    $account = Account::factory()->for($workspace)->create(['name' => 'Cash Wallet']);
    $incomeCategory = Category::factory()->for($workspace)->income()->create(['name' => 'Salary']);
    $expenseCategory = Category::factory()->for($workspace)->expense()->create(['name' => 'Groceries']);

    return [$user, $workspace, $account, $incomeCategory, $expenseCategory];
}

test('transactions can be searched by description', function () {
    [$user, $workspace, $account, $incomeCategory, $expenseCategory] = setUpTransactionSearchWorkspace();

    app(RecordIncomeExpense::class)->record($account, $incomeCategory, TransactionType::Income, 50000, 'Monthly salary', now(), $user);
    app(RecordIncomeExpense::class)->record($account, $expenseCategory, TransactionType::Expense, 15000, 'Weekly groceries', now(), $user);

    $this->actingAs($user)
        ->get(route('transactions.index', ['q' => 'salary']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transactions/Index')
            ->has('transactions.data', 1)
            ->where('transactions.data.0.description', 'Monthly salary')
            ->where('search', 'salary')
        );
});

test('transactions can be searched by memo', function () {
    [$user, $workspace, $account, $incomeCategory] = setUpTransactionSearchWorkspace();

    app(RecordIncomeExpense::class)->record($account, $incomeCategory, TransactionType::Income, 50000, 'Salary', now(), $user, null, 'Bonus included');

    $this->actingAs($user)
        ->get(route('transactions.index', ['q' => 'bonus']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transactions/Index')
            ->has('transactions.data', 1)
        );
});

test('transactions can be searched by merchant', function () {
    [$user, $workspace, $account, $incomeCategory, $expenseCategory] = setUpTransactionSearchWorkspace();
    $merchant = Merchant::factory()->for($workspace)->create(['name' => 'Whole Foods']);

    app(RecordIncomeExpense::class)->record($account, $expenseCategory, TransactionType::Expense, 15000, 'Groceries', now(), $user, $merchant);
    app(RecordIncomeExpense::class)->record($account, $incomeCategory, TransactionType::Income, 50000, 'Salary', now(), $user);

    $this->actingAs($user)
        ->get(route('transactions.index', ['q' => 'whole foods']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transactions/Index')
            ->has('transactions.data', 1)
            ->where('transactions.data.0.description', 'Groceries')
        );
});

test('transactions can be searched by category name', function () {
    [$user, $workspace, $account, $incomeCategory, $expenseCategory] = setUpTransactionSearchWorkspace();

    app(RecordIncomeExpense::class)->record($account, $expenseCategory, TransactionType::Expense, 15000, 'Weekend trip', now(), $user);
    app(RecordIncomeExpense::class)->record($account, $incomeCategory, TransactionType::Income, 50000, 'Pay', now(), $user);

    $this->actingAs($user)
        ->get(route('transactions.index', ['q' => 'groceries']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transactions/Index')
            ->has('transactions.data', 1)
            ->where('transactions.data.0.description', 'Weekend trip')
        );
});

test('transactions can be searched by account name', function () {
    [$user, $workspace, $account, $incomeCategory] = setUpTransactionSearchWorkspace();
    $otherAccount = Account::factory()->for($workspace)->create(['name' => 'Savings']);

    app(RecordIncomeExpense::class)->record($account, $incomeCategory, TransactionType::Income, 50000, 'From cash', now(), $user);
    app(RecordIncomeExpense::class)->record($otherAccount, $incomeCategory, TransactionType::Income, 50000, 'From savings', now(), $user);

    $this->actingAs($user)
        ->get(route('transactions.index', ['q' => 'cash wallet']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transactions/Index')
            ->has('transactions.data', 1)
            ->where('transactions.data.0.description', 'From cash')
        );
});

test('transactions can be searched by amount', function () {
    [$user, $workspace, $account, $incomeCategory, $expenseCategory] = setUpTransactionSearchWorkspace();

    app(RecordIncomeExpense::class)->record($account, $incomeCategory, TransactionType::Income, 50000, 'Salary', now(), $user);
    app(RecordIncomeExpense::class)->record($account, $expenseCategory, TransactionType::Expense, 15000, 'Groceries', now(), $user);

    $this->actingAs($user)
        ->get(route('transactions.index', ['q' => '15000']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transactions/Index')
            ->has('transactions.data', 1)
            ->where('transactions.data.0.description', 'Groceries')
        );
});

test('amount search matches large integers without float precision loss', function () {
    [$user, $workspace, $account, $incomeCategory, $expenseCategory] = setUpTransactionSearchWorkspace();

    // 9007199254740993 exceeds PHP's float precision (2^53); casting it to
    // float would round it to 9007199254740992, the amount of the other
    // transaction below.
    app(RecordIncomeExpense::class)->record($account, $incomeCategory, TransactionType::Income, 9007199254740992, 'Rounded amount', now(), $user);
    app(RecordIncomeExpense::class)->record($account, $expenseCategory, TransactionType::Expense, 9007199254740993, 'Precise amount', now(), $user);

    $this->actingAs($user)
        ->get(route('transactions.index', ['q' => '9007199254740993']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transactions/Index')
            ->has('transactions.data', 1)
            ->where('transactions.data.0.description', 'Precise amount')
        );
});

test('search with no matches returns an empty list', function () {
    [$user, $workspace, $account, $incomeCategory] = setUpTransactionSearchWorkspace();

    app(RecordIncomeExpense::class)->record($account, $incomeCategory, TransactionType::Income, 50000, 'Salary', now(), $user);

    $this->actingAs($user)
        ->get(route('transactions.index', ['q' => 'nonexistent']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transactions/Index')
            ->has('transactions.data', 0)
        );
});
