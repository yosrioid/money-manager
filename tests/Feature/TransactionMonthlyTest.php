<?php

use App\Domain\Transactions\RecordIncomeExpense;
use App\Domain\Workspaces\CreatePersonalWorkspace;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function setUpTransactionMonthlyWorkspace(): array
{
    $user = User::factory()->create();
    $workspace = app(CreatePersonalWorkspace::class)->create($user);
    $account = Account::factory()->for($workspace)->create();
    $incomeCategory = Category::factory()->for($workspace)->income()->create();
    $expenseCategory = Category::factory()->for($workspace)->expense()->create();

    return [$user, $workspace, $account, $incomeCategory, $expenseCategory];
}

test('monthly view summarizes income, expense, net, and counts per month', function () {
    [$user, $workspace, $account, $incomeCategory, $expenseCategory] = setUpTransactionMonthlyWorkspace();

    app(RecordIncomeExpense::class)->record(
        $account,
        $incomeCategory,
        TransactionType::Income,
        50000,
        'Salary',
        now()->setDate(2026, 6, 5)->setTime(1, 0),
        $user,
    );

    app(RecordIncomeExpense::class)->record(
        $account,
        $expenseCategory,
        TransactionType::Expense,
        15000,
        'Groceries',
        now()->setDate(2026, 6, 5)->setTime(10, 0),
        $user,
    );

    app(RecordIncomeExpense::class)->record(
        $account,
        $incomeCategory,
        TransactionType::Income,
        20000,
        'Side income',
        now()->setDate(2026, 7, 1)->setTime(1, 0),
        $user,
    );

    $this->actingAs($user)
        ->get(route('transactions.monthly', ['year' => '2026']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transactions/Monthly')
            ->where('year', '2026')
            ->where('months.2026-06.count', 2)
            ->where('months.2026-06.income.'.$account->currency_code, 50000)
            ->where('months.2026-06.expense.'.$account->currency_code, 15000)
            ->where('months.2026-06.net.'.$account->currency_code, 35000)
            ->where('months.2026-07.count', 1)
            ->where('months.2026-07.income.'.$account->currency_code, 20000)
            ->where('months.2026-07.net.'.$account->currency_code, 20000)
            ->where('previousYear', '2025')
            ->where('nextYear', '2027')
        );
});

test('monthly view excludes transactions outside the requested year', function () {
    [$user, $workspace, $account, $incomeCategory] = setUpTransactionMonthlyWorkspace();

    app(RecordIncomeExpense::class)->record(
        $account,
        $incomeCategory,
        TransactionType::Income,
        10000,
        'Prior year income',
        now()->setDate(2025, 12, 31)->setTime(1, 0),
        $user,
    );

    $this->actingAs($user)
        ->get(route('transactions.monthly', ['year' => '2026']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transactions/Monthly')
            ->where('year', '2026')
            ->where('months', [])
        );
});

test('monthly view defaults to the current workspace-local year', function () {
    [$user, $workspace, $account] = setUpTransactionMonthlyWorkspace();

    $this->actingAs($user)
        ->get(route('transactions.monthly'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transactions/Monthly')
            ->where('year', now($workspace->timezone)->format('Y'))
        );
});
