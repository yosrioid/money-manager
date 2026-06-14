<?php

use App\Domain\Transactions\RecordIncomeExpense;
use App\Domain\Workspaces\CreatePersonalWorkspace;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\CategoryBudgetOverride;
use App\Models\User;
use Carbon\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

function setUpBudgetWorkspace(): array
{
    $user = User::factory()->create();
    $workspace = app(CreatePersonalWorkspace::class)->create($user);
    $account = Account::factory()->for($workspace)->create();

    return [$user, $workspace, $account];
}

test('budgets page shows the default budget and actual spending for the current month', function () {
    [$user, $workspace, $account] = setUpBudgetWorkspace();

    Carbon::setTestNow(now()->setDate(2026, 6, 10)->setTime(12, 0));

    $category = Category::factory()->for($workspace)->expense()->create([
        'monthly_budget_amount' => 500000,
    ]);

    app(RecordIncomeExpense::class)->record($account, $category, TransactionType::Expense, 150000, 'Groceries', now(), $user);

    $this->actingAs($user)->get(route('budgets.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('budgets/Index')
            ->where('month', '2026-06-01')
            ->has('budgets', 1)
            ->where('budgets.0.category_id', $category->id)
            ->where('budgets.0.default_budget', 500000)
            ->where('budgets.0.override', null)
            ->where('budgets.0.budget', 500000)
            ->where('budgets.0.pace', 166667)
            ->where('budgets.0.actual', 150000)
        );

    Carbon::setTestNow();
});

test('an expense category without a budget and without spending is not listed', function () {
    [$user, $workspace] = setUpBudgetWorkspace();

    Category::factory()->for($workspace)->expense()->create([
        'monthly_budget_amount' => null,
    ]);

    $this->actingAs($user)->get(route('budgets.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('budgets/Index')
            ->has('budgets', 0)
        );
});

test('a monthly override replaces the default budget for that period only', function () {
    [$user, $workspace] = setUpBudgetWorkspace();

    Carbon::setTestNow(now()->setDate(2026, 6, 10)->setTime(12, 0));

    $category = Category::factory()->for($workspace)->expense()->create([
        'monthly_budget_amount' => 500000,
    ]);

    $this->actingAs($user)->put(route('budgets.overrides.update', $category), [
        'period' => '2026-06-01',
        'amount' => 750000,
    ])->assertRedirect();

    $this->actingAs($user)->get(route('budgets.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('budgets/Index')
            ->where('budgets.0.override', 750000)
            ->where('budgets.0.budget', 750000)
        );

    $this->actingAs($user)->get(route('budgets.index', ['month' => '2026-07']))
        ->assertInertia(fn (Assert $page) => $page
            ->component('budgets/Index')
            ->where('budgets.0.override', null)
            ->where('budgets.0.budget', 500000)
        );

    Carbon::setTestNow();
});

test('an override can be removed to fall back to the default budget', function () {
    [$user, $workspace] = setUpBudgetWorkspace();

    $category = Category::factory()->for($workspace)->expense()->create([
        'monthly_budget_amount' => 500000,
    ]);

    CategoryBudgetOverride::factory()->for($workspace)->for($category)->create([
        'period' => '2026-06-01',
        'amount' => 750000,
    ]);

    $this->actingAs($user)->delete(route('budgets.overrides.destroy', $category), [
        'period' => '2026-06-01',
    ])->assertRedirect();

    expect($category->budgetOverrides()->count())->toBe(0);
});

test('the recommended spending pace is zero for a future period and the full budget for a past period', function () {
    [$user, $workspace] = setUpBudgetWorkspace();

    Carbon::setTestNow(now()->setDate(2026, 6, 10)->setTime(12, 0));

    Category::factory()->for($workspace)->expense()->create([
        'monthly_budget_amount' => 300000,
    ]);

    $this->actingAs($user)->get(route('budgets.index', ['month' => '2026-07']))
        ->assertInertia(fn (Assert $page) => $page
            ->component('budgets/Index')
            ->where('budgets.0.pace', 0)
        );

    $this->actingAs($user)->get(route('budgets.index', ['month' => '2026-05']))
        ->assertInertia(fn (Assert $page) => $page
            ->component('budgets/Index')
            ->where('budgets.0.pace', 300000)
        );

    Carbon::setTestNow();
});

test('unused budget carries into the next month for a category with carry-over enabled', function () {
    [$user, $workspace, $account] = setUpBudgetWorkspace();

    Carbon::setTestNow(now()->setDate(2026, 6, 10)->setTime(12, 0));

    $category = Category::factory()->for($workspace)->expense()->create([
        'monthly_budget_amount' => 300000,
        'budget_carryover_enabled' => true,
    ]);

    app(RecordIncomeExpense::class)->record($account, $category, TransactionType::Expense, 100000, 'Groceries', now()->setDate(2026, 5, 5), $user);
    app(RecordIncomeExpense::class)->record($account, $category, TransactionType::Expense, 50000, 'Groceries', now()->setDate(2026, 6, 5), $user);

    $this->actingAs($user)->get(route('budgets.index', ['month' => '2026-06']))
        ->assertInertia(fn (Assert $page) => $page
            ->component('budgets/Index')
            ->where('budgets.0.default_budget', 300000)
            ->where('budgets.0.carryover', 200000)
            ->where('budgets.0.budget', 500000)
            ->where('budgets.0.actual', 50000)
        );

    Carbon::setTestNow();
});

test('overspent budget reduces the next month budget for a category with carry-over enabled', function () {
    [$user, $workspace, $account] = setUpBudgetWorkspace();

    Carbon::setTestNow(now()->setDate(2026, 6, 10)->setTime(12, 0));

    $category = Category::factory()->for($workspace)->expense()->create([
        'monthly_budget_amount' => 300000,
        'budget_carryover_enabled' => true,
    ]);

    app(RecordIncomeExpense::class)->record($account, $category, TransactionType::Expense, 400000, 'Groceries', now()->setDate(2026, 5, 5), $user);

    $this->actingAs($user)->get(route('budgets.index', ['month' => '2026-06']))
        ->assertInertia(fn (Assert $page) => $page
            ->component('budgets/Index')
            ->where('budgets.0.default_budget', 300000)
            ->where('budgets.0.carryover', -100000)
            ->where('budgets.0.budget', 200000)
        );

    Carbon::setTestNow();
});

test('a category without carry-over enabled ignores the previous month surplus or deficit', function () {
    [$user, $workspace, $account] = setUpBudgetWorkspace();

    Carbon::setTestNow(now()->setDate(2026, 6, 10)->setTime(12, 0));

    $category = Category::factory()->for($workspace)->expense()->create([
        'monthly_budget_amount' => 300000,
        'budget_carryover_enabled' => false,
    ]);

    app(RecordIncomeExpense::class)->record($account, $category, TransactionType::Expense, 100000, 'Groceries', now()->setDate(2026, 5, 5), $user);

    $this->actingAs($user)->get(route('budgets.index', ['month' => '2026-06']))
        ->assertInertia(fn (Assert $page) => $page
            ->component('budgets/Index')
            ->where('budgets.0.carryover', null)
            ->where('budgets.0.budget', 300000)
        );

    Carbon::setTestNow();
});

test('weekly budgets page shows a prorated budget and actual spending for the week', function () {
    [$user, $workspace, $account] = setUpBudgetWorkspace();

    Carbon::setTestNow(now()->setDate(2026, 6, 10)->setTime(12, 0));

    $category = Category::factory()->for($workspace)->expense()->create([
        'monthly_budget_amount' => 500000,
    ]);

    app(RecordIncomeExpense::class)->record($account, $category, TransactionType::Expense, 20000, 'Snacks', now(), $user);

    $this->actingAs($user)->get(route('budgets.weekly'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('budgets/Weekly')
            ->has('budgets', 1)
            ->where('budgets.0.category_id', $category->id)
            ->where('budgets.0.budget', (int) round(500000 * 7 / 30))
            ->where('budgets.0.actual', 20000)
        );

    Carbon::setTestNow();
});

test('a monthly budget override is prorated for the weekly budget view', function () {
    [$user, $workspace] = setUpBudgetWorkspace();

    Carbon::setTestNow(now()->setDate(2026, 6, 10)->setTime(12, 0));

    $category = Category::factory()->for($workspace)->expense()->create([
        'monthly_budget_amount' => 500000,
    ]);

    CategoryBudgetOverride::factory()->for($workspace)->for($category)->create([
        'period' => '2026-06-01',
        'amount' => 300000,
    ]);

    $this->actingAs($user)->get(route('budgets.weekly'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('budgets/Weekly')
            ->where('budgets.0.budget', (int) round(300000 * 7 / 30))
        );

    Carbon::setTestNow();
});

test('an expense category without a budget and without spending is not listed in the weekly view', function () {
    [$user, $workspace] = setUpBudgetWorkspace();

    Category::factory()->for($workspace)->expense()->create([
        'monthly_budget_amount' => null,
    ]);

    $this->actingAs($user)->get(route('budgets.weekly'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('budgets/Weekly')
            ->has('budgets', 0)
        );
});

test('yearly budgets page sums budget and actual spending across the year', function () {
    [$user, $workspace, $account] = setUpBudgetWorkspace();

    Carbon::setTestNow(now()->setDate(2026, 6, 10)->setTime(12, 0));

    $category = Category::factory()->for($workspace)->expense()->create([
        'monthly_budget_amount' => 100000,
    ]);

    app(RecordIncomeExpense::class)->record($account, $category, TransactionType::Expense, 50000, 'Groceries', now(), $user);

    $this->actingAs($user)->get(route('budgets.yearly'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('budgets/Yearly')
            ->where('year', '2026')
            ->has('budgets', 1)
            ->where('budgets.0.category_id', $category->id)
            ->where('budgets.0.budget', 1200000)
            ->where('budgets.0.actual', 50000)
        );

    Carbon::setTestNow();
});

test('a monthly override adjusts the yearly total budget for that category', function () {
    [$user, $workspace] = setUpBudgetWorkspace();

    Carbon::setTestNow(now()->setDate(2026, 6, 10)->setTime(12, 0));

    $category = Category::factory()->for($workspace)->expense()->create([
        'monthly_budget_amount' => 100000,
    ]);

    CategoryBudgetOverride::factory()->for($workspace)->for($category)->create([
        'period' => '2026-06-01',
        'amount' => 250000,
    ]);

    $this->actingAs($user)->get(route('budgets.yearly'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('budgets/Yearly')
            ->where('budgets.0.budget', 11 * 100000 + 250000)
        );

    Carbon::setTestNow();
});

test('an expense category without a budget and without spending is not listed in the yearly view', function () {
    [$user, $workspace] = setUpBudgetWorkspace();

    Category::factory()->for($workspace)->expense()->create([
        'monthly_budget_amount' => null,
    ]);

    $this->actingAs($user)->get(route('budgets.yearly'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('budgets/Yearly')
            ->has('budgets', 0)
        );
});

test('income budgets page shows planned income and actual income for the current month', function () {
    [$user, $workspace, $account] = setUpBudgetWorkspace();

    Carbon::setTestNow(now()->setDate(2026, 6, 10)->setTime(12, 0));

    $category = Category::factory()->for($workspace)->income()->create([
        'monthly_budget_amount' => 5000000,
    ]);

    app(RecordIncomeExpense::class)->record($account, $category, TransactionType::Income, 4500000, 'Salary', now(), $user);

    $this->actingAs($user)->get(route('budgets.income'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('budgets/Income')
            ->where('month', '2026-06-01')
            ->has('budgets', 1)
            ->where('budgets.0.category_id', $category->id)
            ->where('budgets.0.default_budget', 5000000)
            ->where('budgets.0.override', null)
            ->where('budgets.0.budget', 5000000)
            ->where('budgets.0.pace', 1666667)
            ->where('budgets.0.actual', 4500000)
        );

    Carbon::setTestNow();
});

test('a monthly override replaces the default planned income for that period only', function () {
    [$user, $workspace] = setUpBudgetWorkspace();

    Carbon::setTestNow(now()->setDate(2026, 6, 10)->setTime(12, 0));

    $category = Category::factory()->for($workspace)->income()->create([
        'monthly_budget_amount' => 5000000,
    ]);

    $this->actingAs($user)->put(route('budgets.overrides.update', $category), [
        'period' => '2026-06-01',
        'amount' => 6000000,
    ])->assertRedirect();

    $this->actingAs($user)->get(route('budgets.income'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('budgets/Income')
            ->where('budgets.0.override', 6000000)
            ->where('budgets.0.budget', 6000000)
        );

    Carbon::setTestNow();
});

test('an income category without a planned amount and without income is not listed', function () {
    [$user, $workspace] = setUpBudgetWorkspace();

    Category::factory()->for($workspace)->income()->create([
        'monthly_budget_amount' => null,
    ]);

    $this->actingAs($user)->get(route('budgets.income'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('budgets/Income')
            ->has('budgets', 0)
        );
});

test('budget trend page shows actual versus budget for the past several months', function () {
    [$user, $workspace, $account] = setUpBudgetWorkspace();

    Carbon::setTestNow(now()->setDate(2026, 6, 10)->setTime(12, 0));

    $category = Category::factory()->for($workspace)->expense()->create([
        'monthly_budget_amount' => 500000,
    ]);

    app(RecordIncomeExpense::class)->record($account, $category, TransactionType::Expense, 150000, 'Groceries', now()->setDate(2026, 6, 5), $user);
    app(RecordIncomeExpense::class)->record($account, $category, TransactionType::Expense, 200000, 'Groceries', now()->setDate(2026, 5, 5), $user);

    $this->actingAs($user)->get(route('budgets.trend'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('budgets/Trend')
            ->where('month', '2026-06-01')
            ->has('expense', 6)
            ->where('expense.5.month', '2026-06')
            ->where('expense.5.budget', 500000)
            ->where('expense.5.actual', 150000)
            ->where('expense.4.month', '2026-05')
            ->where('expense.4.budget', 500000)
            ->where('expense.4.actual', 200000)
            ->has('income', 6)
        );

    Carbon::setTestNow();
});

test('a user cannot set a budget override for another workspace category', function () {
    [$user] = setUpBudgetWorkspace();
    [, $otherWorkspace] = setUpBudgetWorkspace();

    $otherCategory = Category::factory()->for($otherWorkspace)->expense()->create([
        'monthly_budget_amount' => 500000,
    ]);

    $this->actingAs($user)->put(route('budgets.overrides.update', $otherCategory), [
        'period' => '2026-06-01',
        'amount' => 750000,
    ])->assertForbidden();
});
