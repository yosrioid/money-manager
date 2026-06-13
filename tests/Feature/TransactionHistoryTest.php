<?php

use App\Domain\Transactions\RecordIncomeExpense;
use App\Domain\Workspaces\CreatePersonalWorkspace;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

function setUpTransactionHistoryWorkspace(): array
{
    $user = User::factory()->create();
    $workspace = app(CreatePersonalWorkspace::class)->create($user);
    $account = Account::factory()->for($workspace)->create();
    $incomeCategory = Category::factory()->for($workspace)->income()->create();
    $expenseCategory = Category::factory()->for($workspace)->expense()->create();

    return [$user, $workspace, $account, $incomeCategory, $expenseCategory];
}

test('posted transactions are listed grouped by workspace-local date', function () {
    [$user, $workspace, $account, $incomeCategory, $expenseCategory] = setUpTransactionHistoryWorkspace();

    app(RecordIncomeExpense::class)->record(
        $account,
        $incomeCategory,
        TransactionType::Income,
        50000,
        'Salary',
        now()->setDate(2026, 6, 1)->setTime(10, 0),
        $user,
    );

    app(RecordIncomeExpense::class)->record(
        $account,
        $expenseCategory,
        TransactionType::Expense,
        15000,
        'Groceries',
        now()->setDate(2026, 6, 2)->setTime(9, 0),
        $user,
    );

    $this->actingAs($user)
        ->get(route('transactions.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transactions/Index')
            ->has('transactions.data', 2)
            ->where('transactions.data.0.description', 'Groceries')
            ->where('transactions.data.0.local_date', '2026-06-02')
            ->where('transactions.data.1.description', 'Salary')
            ->where('transactions.data.1.local_date', '2026-06-01')
        );
});

test('draft transactions do not appear in the history', function () {
    [$user, $workspace, $account, $incomeCategory] = setUpTransactionHistoryWorkspace();

    app(RecordIncomeExpense::class)->record(
        $account,
        $incomeCategory,
        TransactionType::Income,
        50000,
        'Salary',
        now(),
        $user,
    );

    $this->actingAs($user)->post(route('transactions.drafts.store'), [
        'type' => 'expense',
        'account_id' => $account->id,
        'amount' => '5000',
        'description' => 'Pending draft',
        'occurred_at' => now()->toDateTimeString(),
        'idempotency_key' => (string) Str::uuid(),
    ]);

    $this->actingAs($user)
        ->get(route('transactions.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transactions/Index')
            ->has('transactions.data', 1)
            ->where('transactions.data.0.description', 'Salary')
        );
});

test('transactions from another workspace are not visible', function () {
    [$user, $workspace, $account, $incomeCategory] = setUpTransactionHistoryWorkspace();
    [$otherUser, $otherWorkspace, $otherAccount, $otherIncomeCategory] = setUpTransactionHistoryWorkspace();

    app(RecordIncomeExpense::class)->record(
        $otherAccount,
        $otherIncomeCategory,
        TransactionType::Income,
        99999,
        'Other workspace income',
        now(),
        $otherUser,
    );

    $this->actingAs($user)
        ->get(route('transactions.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transactions/Index')
            ->has('transactions.data', 0)
        );
});

test('transaction history is paginated', function () {
    [$user, $workspace, $account, $incomeCategory] = setUpTransactionHistoryWorkspace();

    for ($i = 0; $i < 31; $i++) {
        app(RecordIncomeExpense::class)->record(
            $account,
            $incomeCategory,
            TransactionType::Income,
            1000,
            "Income {$i}",
            now()->subDays($i),
            $user,
        );
    }

    $this->actingAs($user)
        ->get(route('transactions.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transactions/Index')
            ->has('transactions.data', 30)
        );

    $this->actingAs($user)
        ->get(route('transactions.index', ['page' => 2]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transactions/Index')
            ->has('transactions.data', 1)
        );
});
