<?php

use App\Domain\Accounts\CreateAccountWithOpeningBalance;
use App\Domain\Ledger\ReplaceTransaction;
use App\Domain\Ledger\ReverseTransaction;
use App\Domain\Transactions\RecordIncomeExpense;
use App\Domain\Workspaces\CreatePersonalWorkspace;
use App\Enums\AccountType;
use App\Enums\LedgerEntryType;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\User;
use Database\Seeders\CurrencySeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(CurrencySeeder::class);

    $this->user = User::factory()->create();
    $this->workspace = app(CreatePersonalWorkspace::class)->create($this->user);

    $this->account = app(CreateAccountWithOpeningBalance::class)->create($this->workspace, [
        'name' => 'Detail account',
        'type' => AccountType::Cash,
        'currency_code' => 'IDR',
        'opening_balance' => 5000,
    ], $this->user);

    $this->transaction = $this->account->ledgerEntries()->sole()->transaction;
});

test('transaction detail page shows transaction information, entries, and audit history', function () {
    $account = Account::factory()->for($this->workspace)->create();
    $category = Category::factory()->for($this->workspace)->income()->create();
    $income = app(RecordIncomeExpense::class)->record(
        $account,
        $category,
        TransactionType::Income,
        7500,
        'Salary',
        now(),
        $this->user,
    );

    $this->actingAs($this->user)
        ->get(route('transactions.show', $income))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transactions/Show')
            ->where('transaction.id', $income->id)
            ->where('transaction.type', TransactionType::Income->value)
            ->where('transaction.description', 'Salary')
            ->where('transaction.currency_code', 'IDR')
            ->where('transaction.creator.id', $this->user->id)
            ->has('transaction.occurred_at')
            ->where('transaction.posted_at', fn (mixed $value) => $value !== null)
            ->has('transaction.entries', 2)
            ->where('transaction.reverses', null)
            ->where('transaction.reversal', null)
            ->where('transaction.replaces', null)
            ->where('transaction.replacement', null)
            ->has('auditLogs', 1)
            ->where('auditLogs.0.action', 'transaction.posted')
            ->where('auditLogs.0.actor.id', $this->user->id));
});

test('transaction detail page shows reversal links and audit entries', function () {
    $reversal = app(ReverseTransaction::class)->reverse($this->transaction, $this->user);

    $this->actingAs($this->user)
        ->get(route('transactions.show', $this->transaction))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transactions/Show')
            ->where('transaction.reversal.id', $reversal->id)
            ->where('transaction.reverses', null)
            ->has('auditLogs', 1)
            ->where('auditLogs.0.action', 'transaction.reversed'));

    $this->actingAs($this->user)
        ->get(route('transactions.show', $reversal))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transactions/Show')
            ->where('transaction.reverses.id', $this->transaction->id)
            ->where('transaction.reversal', null)
            ->has('auditLogs', 0));
});

test('transaction detail page shows replacement links and audit entries', function () {
    $account = Account::factory()->for($this->workspace)->create();
    $category = Category::factory()->for($this->workspace)->expense()->create();
    $expense = app(RecordIncomeExpense::class)->record(
        $account,
        $category,
        TransactionType::Expense,
        2500,
        'Original expense',
        now(),
        $this->user,
    );

    $replacement = app(ReplaceTransaction::class)->replace($expense, [
        ['account_id' => $account->id, 'category_id' => null, 'type' => LedgerEntryType::Account, 'amount' => -3000],
        ['account_id' => null, 'category_id' => $category->id, 'type' => LedgerEntryType::Category, 'amount' => 3000],
    ], $this->user, 'Replaced expense');

    $this->actingAs($this->user)
        ->get(route('transactions.show', $expense))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transactions/Show')
            ->where('transaction.replacement.id', $replacement->id)
            ->where('transaction.replaces', null)
            ->has('auditLogs', 2)
            ->where('auditLogs.0.action', 'transaction.replaced'));

    $this->actingAs($this->user)
        ->get(route('transactions.show', $replacement))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transactions/Show')
            ->where('transaction.replaces.id', $expense->id)
            ->where('transaction.description', 'Replaced expense')
            ->where('transaction.replacement', null)
            ->has('auditLogs', 0));
});

test('a transaction belonging to another workspace cannot be viewed', function () {
    $outsider = User::factory()->create();
    app(CreatePersonalWorkspace::class)->create($outsider);

    $this->actingAs($outsider)
        ->get(route('transactions.show', $this->transaction))
        ->assertNotFound();
});

test('the transaction detail route does not collide with static transaction routes', function () {
    $this->actingAs($this->user)
        ->get(route('transactions.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('transactions/CreateTransaction'));
});
