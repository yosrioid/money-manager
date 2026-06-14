<?php

use App\Domain\Workspaces\CreatePersonalWorkspace;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\UploadedFile;

function setUpTransactionImportWorkspace(): array
{
    $user = User::factory()->create();
    $workspace = app(CreatePersonalWorkspace::class)->create($user);
    $account = Account::factory()->for($workspace)->create(['name' => 'Cash Wallet']);
    $incomeCategory = Category::factory()->for($workspace)->income()->create(['name' => 'Salary']);
    $expenseCategory = Category::factory()->for($workspace)->expense()->create(['name' => 'Groceries']);

    return [$user, $workspace, $account, $incomeCategory, $expenseCategory];
}

test('guests cannot access the import pages', function () {
    $this->get(route('imports.transactions.create'))->assertRedirect(route('login'));
    $this->post(route('imports.transactions.preview'))->assertRedirect(route('login'));
    $this->post(route('imports.transactions.store'))->assertRedirect(route('login'));
});

test('the import preview validates and resolves rows', function () {
    [$user, $workspace, $account, $incomeCategory, $expenseCategory] = setUpTransactionImportWorkspace();

    $csv = implode("\n", [
        'Date,Type,Description,Memo,Merchant,Account,Category,Amount,Currency,Tags',
        '2026-06-01,income,Salary,,,Cash Wallet,Salary,4500000,IDR,',
        '2026-06-02,expense,Groceries,,,Cash Wallet,Groceries,150000,IDR,',
        '2026-06-03,expense,Unknown account,,,Missing Account,Groceries,1000,IDR,',
    ]);

    $file = UploadedFile::fake()->createWithContent('transactions.csv', $csv);

    $response = $this->actingAs($user)->post(route('imports.transactions.preview'), ['file' => $file]);

    $response->assertInertia(fn ($page) => $page
        ->component('imports/Transactions')
        ->has('token')
        ->where('summary.total', 3)
        ->where('summary.valid', 2)
        ->where('summary.invalid', 1)
        ->where('rows.0.status', 'valid')
        ->where('rows.1.status', 'valid')
        ->where('rows.2.status', 'error')
        ->where('rows.2.errors.0', 'Account "Missing Account" was not found.')
    );
});

test('confirming an import creates transactions and is idempotent on retry', function () {
    [$user, $workspace, $account, $incomeCategory, $expenseCategory] = setUpTransactionImportWorkspace();

    $csv = implode("\n", [
        'Date,Type,Description,Memo,Merchant,Account,Category,Amount,Currency,Tags',
        '2026-06-01,income,Salary,,,Cash Wallet,Salary,4500000,IDR,',
        '2026-06-02,expense,Groceries,,,Cash Wallet,Groceries,150000,IDR,',
    ]);

    $file = UploadedFile::fake()->createWithContent('transactions.csv', $csv);

    $preview = $this->actingAs($user)->post(route('imports.transactions.preview'), ['file' => $file]);
    $token = $preview->viewData('page')['props']['token'];

    $this->actingAs($user)->post(route('imports.transactions.store'), ['token' => $token])
        ->assertRedirect(route('transactions.index'));

    expect(Transaction::query()->where('workspace_id', $workspace->id)->count())->toBe(2);

    // Re-import the same file; the idempotency key must prevent duplicates.
    $secondFile = UploadedFile::fake()->createWithContent('transactions.csv', $csv);
    $secondPreview = $this->actingAs($user)->post(route('imports.transactions.preview'), ['file' => $secondFile]);
    $secondToken = $secondPreview->viewData('page')['props']['token'];

    $this->actingAs($user)->post(route('imports.transactions.store'), ['token' => $secondToken])
        ->assertRedirect(route('transactions.index'));

    expect(Transaction::query()->where('workspace_id', $workspace->id)->count())->toBe(2);
});
