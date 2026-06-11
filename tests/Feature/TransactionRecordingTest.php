<?php

use App\Domain\Ledger\CalculateAccountBalance;
use App\Domain\Workspaces\CreatePersonalWorkspace;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function createTransactionWorkspace(): array
{
    $user = User::factory()->create();
    $workspace = app(CreatePersonalWorkspace::class)->create($user);

    return [$user, $workspace];
}

test('user can record an income transaction', function () {
    [$user, $workspace] = createTransactionWorkspace();
    $account = Account::factory()->for($workspace)->create();
    $category = Category::factory()->for($workspace)->income()->create();

    $this->actingAs($user)
        ->post(route('transactions.store'), [
            'type' => 'income',
            'account_id' => $account->id,
            'category_id' => $category->id,
            'amount' => 50000,
            'description' => 'Freelance payment',
            'occurred_at' => now()->toDateTimeString(),
        ])
        ->assertRedirect(route('accounts.index'));

    $transaction = Transaction::query()->sole();

    expect($transaction)
        ->type->toBe(TransactionType::Income)
        ->status->toBe(TransactionStatus::Posted);

    expect($transaction->entries()->sum('amount'))->toBe(0);
    expect(app(CalculateAccountBalance::class)->calculate($account->fresh()))->toBe(50000);
});

test('user can record an expense transaction', function () {
    [$user, $workspace] = createTransactionWorkspace();
    $account = Account::factory()->for($workspace)->create();
    $category = Category::factory()->for($workspace)->expense()->create();

    $this->actingAs($user)
        ->post(route('transactions.store'), [
            'type' => 'expense',
            'account_id' => $account->id,
            'category_id' => $category->id,
            'amount' => 15000,
            'description' => 'Groceries',
            'occurred_at' => now()->toDateTimeString(),
        ])
        ->assertRedirect(route('accounts.index'));

    $transaction = Transaction::query()->sole();

    expect($transaction)
        ->type->toBe(TransactionType::Expense)
        ->status->toBe(TransactionStatus::Posted);

    expect($transaction->entries()->sum('amount'))->toBe(0);
    expect(app(CalculateAccountBalance::class)->calculate($account->fresh()))->toBe(-15000);
});

test('category type must match transaction type', function () {
    [$user, $workspace] = createTransactionWorkspace();
    $account = Account::factory()->for($workspace)->create();
    $expenseCategory = Category::factory()->for($workspace)->expense()->create();

    $this->actingAs($user)
        ->post(route('transactions.store'), [
            'type' => 'income',
            'account_id' => $account->id,
            'category_id' => $expenseCategory->id,
            'amount' => 1000,
            'description' => 'Mismatched category',
            'occurred_at' => now()->toDateTimeString(),
        ])
        ->assertSessionHasErrors('category_id');

    expect(Transaction::query()->count())->toBe(0);
});

test('account and category from another workspace are rejected', function () {
    [$user, $workspace] = createTransactionWorkspace();
    [, $otherWorkspace] = createTransactionWorkspace();

    $account = Account::factory()->for($workspace)->create();
    $category = Category::factory()->for($workspace)->expense()->create();
    $otherAccount = Account::factory()->for($otherWorkspace)->create();
    $otherCategory = Category::factory()->for($otherWorkspace)->expense()->create();

    $this->actingAs($user)
        ->post(route('transactions.store'), [
            'type' => 'expense',
            'account_id' => $otherAccount->id,
            'category_id' => $category->id,
            'amount' => 1000,
            'description' => 'Other workspace account',
            'occurred_at' => now()->toDateTimeString(),
        ])
        ->assertSessionHasErrors('account_id');

    $this->actingAs($user)
        ->post(route('transactions.store'), [
            'type' => 'expense',
            'account_id' => $account->id,
            'category_id' => $otherCategory->id,
            'amount' => 1000,
            'description' => 'Other workspace category',
            'occurred_at' => now()->toDateTimeString(),
        ])
        ->assertSessionHasErrors('category_id');

    expect(Transaction::query()->count())->toBe(0);
});

test('amount must be a positive integer', function () {
    [$user, $workspace] = createTransactionWorkspace();
    $account = Account::factory()->for($workspace)->create();
    $category = Category::factory()->for($workspace)->expense()->create();

    $this->actingAs($user)
        ->post(route('transactions.store'), [
            'type' => 'expense',
            'account_id' => $account->id,
            'category_id' => $category->id,
            'amount' => 0,
            'description' => 'Zero amount',
            'occurred_at' => now()->toDateTimeString(),
        ])
        ->assertSessionHasErrors('amount');

    expect(Transaction::query()->count())->toBe(0);
});

test('transaction create page renders accounts and categories', function () {
    [$user, $workspace] = createTransactionWorkspace();
    Account::factory()->for($workspace)->create();
    Category::factory()->for($workspace)->expense()->create();
    Category::factory()->for($workspace)->income()->create();

    $this->actingAs($user)
        ->get(route('transactions.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transactions/CreateTransaction')
            ->has('accounts', 1)
            ->has('categories', 2),
        );
});
