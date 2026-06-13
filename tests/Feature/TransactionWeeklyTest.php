<?php

use App\Domain\Ledger\ReverseTransaction;
use App\Domain\Transactions\RecordIncomeExpense;
use App\Domain\Workspaces\CreatePersonalWorkspace;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

function setUpTransactionWeeklyWorkspace(): array
{
    $user = User::factory()->create();
    $workspace = app(CreatePersonalWorkspace::class)->create($user);
    $account = Account::factory()->for($workspace)->create();
    $incomeCategory = Category::factory()->for($workspace)->income()->create();
    $expenseCategory = Category::factory()->for($workspace)->expense()->create();

    return [$user, $workspace, $account, $incomeCategory, $expenseCategory];
}

test('weekly view summarizes daily totals and weekly totals', function () {
    [$user, $workspace, $account, $incomeCategory, $expenseCategory] = setUpTransactionWeeklyWorkspace();

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
        ->get(route('transactions.weekly', ['week' => '2026-06-05']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transactions/Weekly')
            ->where('weekStart', '2026-06-01')
            ->where('days.2026-06-05.count', 2)
            ->where('days.2026-06-05.income.'.$account->currency_code, 50000)
            ->where('days.2026-06-05.expense.'.$account->currency_code, 15000)
            ->where('days.2026-06-05.net.'.$account->currency_code, 35000)
            ->where('totals.income.'.$account->currency_code, 50000)
            ->where('totals.expense.'.$account->currency_code, 15000)
            ->where('totals.net.'.$account->currency_code, 35000)
            ->where('previousWeek', '2026-05-25')
            ->where('nextWeek', '2026-06-08')
        );
});

test('weekly view starts on Sunday when the workspace prefers a Sunday-first week', function () {
    [$user, $workspace, $account, $incomeCategory, $expenseCategory] = setUpTransactionWeeklyWorkspace();

    $workspace->update(['first_day_of_week' => 0]);

    app(RecordIncomeExpense::class)->record(
        $account,
        $incomeCategory,
        TransactionType::Income,
        50000,
        'Salary',
        now()->setDate(2026, 6, 5)->setTime(1, 0),
        $user,
    );

    $this->actingAs($user)
        ->get(route('transactions.weekly', ['week' => '2026-06-05']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transactions/Weekly')
            ->where('weekStart', '2026-05-31')
            ->where('days.2026-06-05.income.'.$account->currency_code, 50000)
            ->where('previousWeek', '2026-05-24')
            ->where('nextWeek', '2026-06-07')
        );
});

test('weekly view nets a same-day reversal to zero without double counting', function () {
    [$user, $workspace, $account, , $expenseCategory] = setUpTransactionWeeklyWorkspace();

    Carbon::setTestNow(now()->setDate(2026, 6, 5)->setTime(10, 0));

    $expense = app(RecordIncomeExpense::class)->record(
        $account,
        $expenseCategory,
        TransactionType::Expense,
        15000,
        'Groceries',
        now(),
        $user,
    );

    app(ReverseTransaction::class)->reverse($expense, $user);

    $this->actingAs($user)
        ->get(route('transactions.weekly', ['week' => '2026-06-05']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transactions/Weekly')
            ->where('days.2026-06-05.count', 2)
            ->where('days.2026-06-05.income.'.$account->currency_code, 15000)
            ->where('days.2026-06-05.expense.'.$account->currency_code, 15000)
            ->where('days.2026-06-05.net.'.$account->currency_code, 0)
            ->where('totals.income.'.$account->currency_code, 15000)
            ->where('totals.expense.'.$account->currency_code, 15000)
            ->where('totals.net.'.$account->currency_code, 0)
        );

    Carbon::setTestNow();
});

test('weekly view excludes transactions outside the requested week', function () {
    [$user, $workspace, $account, $incomeCategory] = setUpTransactionWeeklyWorkspace();

    app(RecordIncomeExpense::class)->record(
        $account,
        $incomeCategory,
        TransactionType::Income,
        10000,
        'Earlier week income',
        now()->setDate(2026, 5, 30)->setTime(1, 0),
        $user,
    );

    $this->actingAs($user)
        ->get(route('transactions.weekly', ['week' => '2026-06-05']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transactions/Weekly')
            ->where('weekStart', '2026-06-01')
            ->where('days', [])
        );
});

test('weekly view defaults to the current workspace-local week', function () {
    [$user, $workspace, $account] = setUpTransactionWeeklyWorkspace();

    $now = now($workspace->timezone);
    $expectedWeekStart = $now->copy()
        ->subDays(($now->dayOfWeek - $workspace->first_day_of_week + 7) % 7)
        ->startOfDay();

    $this->actingAs($user)
        ->get(route('transactions.weekly'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transactions/Weekly')
            ->where('weekStart', $expectedWeekStart->toDateString())
        );
});

test('day view lists posted transactions for the requested workspace-local date', function () {
    [$user, $workspace, $account, $incomeCategory, $expenseCategory] = setUpTransactionWeeklyWorkspace();

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
        'Other day income',
        now()->setDate(2026, 6, 6)->setTime(1, 0),
        $user,
    );

    $this->actingAs($user)
        ->get(route('transactions.day', ['date' => '2026-06-05']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transactions/Day')
            ->where('date', '2026-06-05')
            ->has('transactions', 2)
            ->where('transactions.0.description', 'Groceries')
            ->where('transactions.1.description', 'Salary')
            ->where('previousDate', '2026-06-04')
            ->where('nextDate', '2026-06-06')
        );
});

test('day view defaults to the current workspace-local date', function () {
    [$user, $workspace, $account] = setUpTransactionWeeklyWorkspace();

    $this->actingAs($user)
        ->get(route('transactions.day'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transactions/Day')
            ->where('date', now($workspace->timezone)->startOfDay()->toDateString())
        );
});
