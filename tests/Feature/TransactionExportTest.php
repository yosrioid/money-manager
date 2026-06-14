<?php

use App\Domain\Transactions\RecordIncomeExpense;
use App\Domain\Transactions\RecordTransfer;
use App\Domain\Workspaces\CreatePersonalWorkspace;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\User;

function setUpTransactionExportWorkspace(): array
{
    $user = User::factory()->create();
    $workspace = app(CreatePersonalWorkspace::class)->create($user);
    $account = Account::factory()->for($workspace)->create(['name' => 'Cash Wallet']);
    $incomeCategory = Category::factory()->for($workspace)->income()->create(['name' => 'Salary']);
    $expenseCategory = Category::factory()->for($workspace)->expense()->create(['name' => 'Groceries']);

    return [$user, $workspace, $account, $incomeCategory, $expenseCategory];
}

test('guests cannot export transactions', function () {
    $this->get(route('transactions.export'))->assertRedirect(route('login'));
});

test('the csv export includes a header row and posted transactions', function () {
    [$user, $workspace, $account, $incomeCategory, $expenseCategory] = setUpTransactionExportWorkspace();

    app(RecordIncomeExpense::class)->record($account, $incomeCategory, TransactionType::Income, 50000, 'Salary', now(), $user);
    app(RecordIncomeExpense::class)->record($account, $expenseCategory, TransactionType::Expense, 15000, 'Groceries', now(), $user);

    $response = $this->actingAs($user)->get(route('transactions.export'));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

    $content = $response->streamedContent();
    $rows = array_map('str_getcsv', array_values(array_filter(explode("\n", $content))));

    $today = now()->toDateString();

    expect($rows[0])->toBe(['Date', 'Type', 'Status', 'Description', 'Memo', 'Merchant', 'Account', 'Category', 'Amount', 'Currency', 'Tags']);
    expect($rows[1])->toBe([$today, 'income', 'posted', 'Salary', '', '', 'Cash Wallet', 'Salary', '50000', 'IDR', '']);
    expect($rows[2])->toBe([$today, 'expense', 'posted', 'Groceries', '', '', 'Cash Wallet', 'Groceries', '-15000', 'IDR', '']);
});

test('the csv export produces a row per account for transfers', function () {
    [$user, $workspace, $account] = setUpTransactionExportWorkspace();
    $savings = Account::factory()->for($workspace)->create(['name' => 'Savings']);

    app(RecordTransfer::class)->record($account, $savings, 10000, 0, null, 'Move funds', now(), $user);

    $response = $this->actingAs($user)->get(route('transactions.export'));

    $content = $response->streamedContent();
    $rows = array_map('str_getcsv', array_values(array_filter(explode("\n", $content))));

    expect($rows[1])->toBe([now()->toDateString(), 'transfer', 'posted', 'Move funds', '', '', 'Cash Wallet', '', '-10000', 'IDR', '']);
    expect($rows[2])->toBe([now()->toDateString(), 'transfer', 'posted', 'Move funds', '', '', 'Savings', '', '10000', 'IDR', '']);
});

test('the csv export escapes values that look like spreadsheet formulas', function () {
    [$user, $workspace, $account, $incomeCategory] = setUpTransactionExportWorkspace();

    app(RecordIncomeExpense::class)->record($account, $incomeCategory, TransactionType::Income, 50000, '=cmd|"/c calc"!A1', now(), $user);

    $response = $this->actingAs($user)->get(route('transactions.export'));

    $content = $response->streamedContent();
    $rows = array_map('str_getcsv', array_values(array_filter(explode("\n", $content))));

    expect($rows[1][3])->toBe('\'=cmd|"/c calc"!A1');
});

test('the csv export applies the requested filters', function () {
    [$user, $workspace, $account, $incomeCategory, $expenseCategory] = setUpTransactionExportWorkspace();

    app(RecordIncomeExpense::class)->record($account, $incomeCategory, TransactionType::Income, 50000, 'Salary', now(), $user);
    app(RecordIncomeExpense::class)->record($account, $expenseCategory, TransactionType::Expense, 15000, 'Groceries', now(), $user);

    $response = $this->actingAs($user)->get(route('transactions.export', ['type' => 'expense']));

    $content = $response->streamedContent();

    expect($content)->toContain('Groceries');
    expect($content)->not->toContain('Salary');
});
