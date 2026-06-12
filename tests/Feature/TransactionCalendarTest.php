<?php

use App\Domain\Transactions\RecordIncomeExpense;
use App\Domain\Workspaces\CreatePersonalWorkspace;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function setUpTransactionCalendarWorkspace(): array
{
    $user = User::factory()->create();
    $workspace = app(CreatePersonalWorkspace::class)->create($user);
    $account = Account::factory()->for($workspace)->create();
    $incomeCategory = Category::factory()->for($workspace)->income()->create();
    $expenseCategory = Category::factory()->for($workspace)->expense()->create();

    return [$user, $workspace, $account, $incomeCategory, $expenseCategory];
}

test('calendar summarizes daily income, expense, net, and record counts', function () {
    [$user, $workspace, $account, $incomeCategory, $expenseCategory] = setUpTransactionCalendarWorkspace();

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

    $this->actingAs($user)
        ->get(route('transactions.calendar', ['month' => '2026-06']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transactions/Calendar')
            ->where('month', '2026-06-01')
            ->where('days.2026-06-05.count', 2)
            ->where('days.2026-06-05.income.'.$account->currency_code, 50000)
            ->where('days.2026-06-05.expense.'.$account->currency_code, 15000)
            ->where('days.2026-06-05.net.'.$account->currency_code, 35000)
            ->where('previousMonth', '2026-05')
            ->where('nextMonth', '2026-07')
        );
});

test('calendar excludes transactions outside the requested month', function () {
    [$user, $workspace, $account, $incomeCategory] = setUpTransactionCalendarWorkspace();

    app(RecordIncomeExpense::class)->record(
        $account,
        $incomeCategory,
        TransactionType::Income,
        10000,
        'May income',
        now()->setDate(2026, 5, 31)->setTime(1, 0),
        $user,
    );

    $this->actingAs($user)
        ->get(route('transactions.calendar', ['month' => '2026-06']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transactions/Calendar')
            ->where('days', [])
        );
});

test('calendar defaults to the current workspace-local month', function () {
    [$user, $workspace, $account] = setUpTransactionCalendarWorkspace();

    $this->actingAs($user)
        ->get(route('transactions.calendar'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transactions/Calendar')
            ->where('month', now($workspace->timezone)->startOfMonth()->toDateString())
        );
});
