<?php

use App\Domain\Ledger\ReverseTransaction;
use App\Domain\Transactions\RecordIncomeExpense;
use App\Domain\Workspaces\CreatePersonalWorkspace;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\User;
use Database\Seeders\CurrencySeeder;
use Illuminate\Support\Carbon;
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
            ->where('budgetSummary.expense.actual', 15000)
            ->where('budgetSummary.income.actual', 50000)
        );
});

test('summary view compares total actual spending and income with the total budget', function () {
    [$user, $workspace, $account, $incomeCategory, $expenseCategory] = setUpTransactionSummaryWorkspace();

    $expenseCategory->update(['monthly_budget_amount' => 20000]);
    $incomeCategory->update(['monthly_budget_amount' => 60000]);

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
            ->where('budgetSummary.expense.budget', 20000)
            ->where('budgetSummary.expense.actual', 15000)
            ->where('budgetSummary.income.budget', 60000)
            ->where('budgetSummary.income.actual', 50000)
        );
});

test('summary view shows the net asset total against an optional workspace target', function () {
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
            ->where('netAssets.'.$account->currency_code, 35000)
            ->where('netAssetBase', 35000)
            ->where('unsupportedCurrencies', [])
            ->where('netAssetTarget', null)
            ->where('defaultCurrency', $workspace->default_currency)
        );

    $workspace->update(['net_asset_target' => 100000]);

    $this->actingAs($user)
        ->get(route('transactions.summary', ['month' => '2026-06']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transactions/Summary')
            ->where('netAssets.'.$account->currency_code, 35000)
            ->where('netAssetTarget', 100000)
        );
});

test('summary view converts non-default currency balances using configured exchange rates', function () {
    $this->seed(CurrencySeeder::class);

    [$user, $workspace, $account, $incomeCategory] = setUpTransactionSummaryWorkspace();

    expect($workspace->default_currency)->toBe('IDR');

    $usdAccount = Account::factory()->for($workspace)->create(['currency_code' => 'USD']);
    $eurAccount = Account::factory()->for($workspace)->create(['currency_code' => 'EUR']);

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
        $usdAccount,
        $incomeCategory,
        TransactionType::Income,
        100,
        'Freelance',
        now()->setDate(2026, 6, 5)->setTime(1, 0),
        $user,
    );

    app(RecordIncomeExpense::class)->record(
        $eurAccount,
        $incomeCategory,
        TransactionType::Income,
        10,
        'Gift',
        now()->setDate(2026, 6, 5)->setTime(1, 0),
        $user,
    );

    $workspace->exchangeRates()->create(['currency_code' => 'USD', 'rate_to_base' => '16000.0000000000']);

    $this->actingAs($user)
        ->get(route('transactions.summary', ['month' => '2026-06']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transactions/Summary')
            ->where('netAssets.IDR', 50000)
            ->where('netAssets.USD', 100)
            ->where('netAssets.EUR', 10)
            ->where('netAssetBase', 50000 + 100 * 16000)
            ->where('unsupportedCurrencies', ['EUR'])
        );
});

test('summary view nets a same-period reversal to zero without double counting', function () {
    [$user, $workspace, $account, , $expenseCategory] = setUpTransactionSummaryWorkspace();

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
        ->get(route('transactions.summary', ['month' => '2026-06']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transactions/Summary')
            ->where('month', '2026-06-01')
            ->where('count', 2)
            ->where('totals.income.'.$account->currency_code, 15000)
            ->where('totals.expense.'.$account->currency_code, 15000)
            ->where('totals.net.'.$account->currency_code, 0)
            ->has('accountMovements', 1)
            ->where('accountMovements.0.id', $account->id)
            ->where('accountMovements.0.change', 0)
        );

    Carbon::setTestNow();
});

test('summary view falls back to the current month when given an invalid month', function () {
    [$user, $workspace] = setUpTransactionSummaryWorkspace();

    $this->actingAs($user)
        ->get(route('transactions.summary', ['month' => '2026-99']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transactions/Summary')
            ->where('month', now($workspace->timezone)->startOfMonth()->toDateString())
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
