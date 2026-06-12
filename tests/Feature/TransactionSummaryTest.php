<?php

use App\Domain\Transactions\RecordIncomeExpense;
use App\Domain\Workspaces\CreatePersonalWorkspace;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function setUpTransactionSummaryWorkspace(): array
{
    $user = User::factory()->create();
    $workspace = app(CreatePersonalWorkspace::class)->create($user);
    $account = Account::factory()->for($workspace)->create();
    $incomeCategory = Category::factory()->for($workspace)->income()->create();
    $expenseCategory = Category::factory()->for($workspace)->expense()->create();

    return [$user, $workspace, $account, $incomeCategory, $expenseCategory];
}

test('summary view shows period totals and account movement', function () {
    [$user, $workspace, $account, $incomeCategory, $expenseCategory] = setUpTransactionSummaryWorkspace();

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
        ->get(route('transactions.summary', ['month' => '2026-06']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transactions/Summary')
            ->where('month', '2026-06-01')
            ->where('count', 2)
            ->where('totals.income.'.$account->currency_code, 50000)
            ->where('totals.expense.'.$account->currency_code, 15000)
            ->where('totals.net.'.$account->currency_code, 35000)
            ->where('previousMonth', '2026-05')
            ->where('nextMonth', '2026-07')
            ->has('accountMovements', 1)
            ->where('accountMovements.0.id', $account->id)
            ->where('accountMovements.0.change', 35000)
        );
});

test('summary view defaults to the current workspace-local month', function () {
    [$user, $workspace, $account] = setUpTransactionSummaryWorkspace();

    $this->actingAs($user)
        ->get(route('transactions.summary'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transactions/Summary')
            ->where('month', now($workspace->timezone)->startOfMonth()->toDateString())
        );
});
