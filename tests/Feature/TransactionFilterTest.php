<?php

use App\Domain\Transactions\RecordIncomeExpense;
use App\Domain\Transactions\RecordTransfer;
use App\Domain\Workspaces\CreatePersonalWorkspace;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Tag;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function setUpTransactionFilterWorkspace(): array
{
    $user = User::factory()->create();
    $workspace = app(CreatePersonalWorkspace::class)->create($user);
    $account = Account::factory()->for($workspace)->create(['name' => 'Cash Wallet']);
    $incomeCategory = Category::factory()->for($workspace)->income()->create(['name' => 'Salary']);
    $expenseCategory = Category::factory()->for($workspace)->expense()->create(['name' => 'Groceries']);

    return [$user, $workspace, $account, $incomeCategory, $expenseCategory];
}

test('transactions can be filtered by type', function () {
    [$user, $workspace, $account, $incomeCategory, $expenseCategory] = setUpTransactionFilterWorkspace();

    app(RecordIncomeExpense::class)->record($account, $incomeCategory, TransactionType::Income, 50000, 'Salary', now(), $user);
    app(RecordIncomeExpense::class)->record($account, $expenseCategory, TransactionType::Expense, 15000, 'Groceries', now(), $user);

    $this->actingAs($user)
        ->get(route('transactions.index', ['type' => 'expense']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transactions/Index')
            ->has('transactions.data', 1)
            ->where('transactions.data.0.description', 'Groceries')
            ->where('filters.type', 'expense')
        );
});

test('transactions can be filtered by category', function () {
    [$user, $workspace, $account, $incomeCategory, $expenseCategory] = setUpTransactionFilterWorkspace();

    app(RecordIncomeExpense::class)->record($account, $incomeCategory, TransactionType::Income, 50000, 'Salary', now(), $user);
    app(RecordIncomeExpense::class)->record($account, $expenseCategory, TransactionType::Expense, 15000, 'Groceries', now(), $user);

    $this->actingAs($user)
        ->get(route('transactions.index', ['category_id' => $expenseCategory->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transactions/Index')
            ->has('transactions.data', 1)
            ->where('transactions.data.0.description', 'Groceries')
        );
});

test('transactions can be filtered by account', function () {
    [$user, $workspace, $account, $incomeCategory] = setUpTransactionFilterWorkspace();
    $otherAccount = Account::factory()->for($workspace)->create(['name' => 'Savings']);

    app(RecordIncomeExpense::class)->record($account, $incomeCategory, TransactionType::Income, 50000, 'From cash', now(), $user);
    app(RecordIncomeExpense::class)->record($otherAccount, $incomeCategory, TransactionType::Income, 50000, 'From savings', now(), $user);

    $this->actingAs($user)
        ->get(route('transactions.index', ['account_id' => $otherAccount->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transactions/Index')
            ->has('transactions.data', 1)
            ->where('transactions.data.0.description', 'From savings')
        );
});

test('transactions can be filtered by tag', function () {
    [$user, $workspace, $account, $incomeCategory, $expenseCategory] = setUpTransactionFilterWorkspace();
    $tag = Tag::factory()->for($workspace)->create(['name' => 'Work']);

    app(RecordIncomeExpense::class)->record($account, $incomeCategory, TransactionType::Income, 50000, 'Salary', now(), $user, null, null, [$tag]);
    app(RecordIncomeExpense::class)->record($account, $expenseCategory, TransactionType::Expense, 15000, 'Groceries', now(), $user);

    $this->actingAs($user)
        ->get(route('transactions.index', ['tag_id' => $tag->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transactions/Index')
            ->has('transactions.data', 1)
            ->where('transactions.data.0.description', 'Salary')
        );
});

test('transactions can be filtered by status', function () {
    [$user, $workspace, $account, $incomeCategory, $expenseCategory] = setUpTransactionFilterWorkspace();
    $otherAccount = Account::factory()->for($workspace)->create();

    $transfer = app(RecordTransfer::class)->record($account, $otherAccount, 10000, 0, null, 'Move funds', now(), $user);
    app(RecordIncomeExpense::class)->record($account, $incomeCategory, TransactionType::Income, 50000, 'Salary', now(), $user);

    expect($transfer)->not->toBeNull();

    $this->actingAs($user)
        ->get(route('transactions.index', ['status' => 'posted', 'type' => 'transfer']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transactions/Index')
            ->has('transactions.data', 1)
            ->where('transactions.data.0.description', 'Move funds')
        );
});

test('transactions can be filtered by date range', function () {
    [$user, $workspace, $account, $incomeCategory] = setUpTransactionFilterWorkspace();

    app(RecordIncomeExpense::class)->record($account, $incomeCategory, TransactionType::Income, 50000, 'June 1', now()->setDate(2026, 6, 1)->setTime(12, 0), $user);
    app(RecordIncomeExpense::class)->record($account, $incomeCategory, TransactionType::Income, 50000, 'June 10', now()->setDate(2026, 6, 10)->setTime(12, 0), $user);
    app(RecordIncomeExpense::class)->record($account, $incomeCategory, TransactionType::Income, 50000, 'June 20', now()->setDate(2026, 6, 20)->setTime(12, 0), $user);

    $this->actingAs($user)
        ->get(route('transactions.index', ['from' => '2026-06-05', 'to' => '2026-06-15']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transactions/Index')
            ->has('transactions.data', 1)
            ->where('transactions.data.0.description', 'June 10')
            ->where('filters.from', '2026-06-05')
            ->where('filters.to', '2026-06-15')
        );
});

test('filters combine with search', function () {
    [$user, $workspace, $account, $incomeCategory, $expenseCategory] = setUpTransactionFilterWorkspace();

    app(RecordIncomeExpense::class)->record($account, $incomeCategory, TransactionType::Income, 50000, 'Salary bonus', now(), $user);
    app(RecordIncomeExpense::class)->record($account, $expenseCategory, TransactionType::Expense, 15000, 'Groceries bonus', now(), $user);

    $this->actingAs($user)
        ->get(route('transactions.index', ['q' => 'bonus', 'type' => 'income']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transactions/Index')
            ->has('transactions.data', 1)
            ->where('transactions.data.0.description', 'Salary bonus')
        );
});

test('an invalid date filter is ignored instead of erroring', function () {
    [$user, $workspace, $account, $incomeCategory] = setUpTransactionFilterWorkspace();

    app(RecordIncomeExpense::class)->record($account, $incomeCategory, TransactionType::Income, 50000, 'June 10', now()->setDate(2026, 6, 10)->setTime(12, 0), $user);

    $this->actingAs($user)
        ->get(route('transactions.index', ['from' => '2026-99-01', 'to' => '2026-02-31']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transactions/Index')
            ->has('transactions.data', 1)
            ->where('filters.from', null)
            ->where('filters.to', null)
        );
});

test('an invalid filter value is ignored', function () {
    [$user, $workspace, $account, $incomeCategory] = setUpTransactionFilterWorkspace();

    app(RecordIncomeExpense::class)->record($account, $incomeCategory, TransactionType::Income, 50000, 'Salary', now(), $user);

    $this->actingAs($user)
        ->get(route('transactions.index', ['type' => 'not-a-real-type', 'status' => 'also-fake']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transactions/Index')
            ->has('transactions.data', 1)
            ->where('filters.type', null)
            ->where('filters.status', null)
        );
});
