<?php

use App\Domain\Transactions\RecordIncomeExpense;
use App\Domain\Workspaces\CreatePersonalWorkspace;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function setUpTransactionBulkWorkspace(): array
{
    $user = User::factory()->create();
    $workspace = app(CreatePersonalWorkspace::class)->create($user);
    $account = Account::factory()->for($workspace)->create();
    $expenseCategory = Category::factory()->for($workspace)->expense()->create();

    return [$user, $workspace, $account, $expenseCategory];
}

test('duplicating a single selected transaction redirects to the new draft', function () {
    [$user, , $account, $expenseCategory] = setUpTransactionBulkWorkspace();

    $transaction = app(RecordIncomeExpense::class)->record(
        $account,
        $expenseCategory,
        TransactionType::Expense,
        2500,
        'Coffee',
        now(),
        $user,
    );

    $response = $this->actingAs($user)
        ->post(route('transactions.bulk-duplicate'), [
            'transaction_ids' => [$transaction->id],
        ]);

    $draft = Transaction::query()->where('status', TransactionStatus::Draft)->sole();

    $response->assertRedirect(route('transactions.drafts.edit', $draft));
});

test('duplicating multiple selected transactions creates a draft for each and redirects to the drafts list', function () {
    [$user, , $account, $expenseCategory] = setUpTransactionBulkWorkspace();

    $first = app(RecordIncomeExpense::class)->record(
        $account,
        $expenseCategory,
        TransactionType::Expense,
        2500,
        'Coffee',
        now(),
        $user,
    );

    $second = app(RecordIncomeExpense::class)->record(
        $account,
        $expenseCategory,
        TransactionType::Expense,
        4000,
        'Lunch',
        now(),
        $user,
    );

    $response = $this->actingAs($user)
        ->post(route('transactions.bulk-duplicate'), [
            'transaction_ids' => [$first->id, $second->id],
        ]);

    $response->assertRedirect(route('transactions.drafts.index'));

    expect(Transaction::query()->where('status', TransactionStatus::Draft)->count())->toBe(2)
        ->and(Transaction::query()->where('status', TransactionStatus::Draft)->pluck('description')->all())
        ->toEqual(['Coffee', 'Lunch']);
});

test('the drafts index page lists draft transactions with a resume link', function () {
    [$user, , $account, $expenseCategory] = setUpTransactionBulkWorkspace();

    $transaction = app(RecordIncomeExpense::class)->record(
        $account,
        $expenseCategory,
        TransactionType::Expense,
        2500,
        'Coffee',
        now(),
        $user,
    );

    $this->actingAs($user)->post(route('transactions.bulk-duplicate'), [
        'transaction_ids' => [$transaction->id],
    ]);

    $draft = Transaction::query()->where('status', TransactionStatus::Draft)->sole();

    $this->actingAs($user)
        ->get(route('transactions.drafts.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transactions/Drafts')
            ->has('drafts', 1)
            ->where('drafts.0.id', $draft->id)
            ->where('drafts.0.description', $draft->description));
});

test('a transaction belonging to another workspace cannot be bulk duplicated', function () {
    [$user, , $account, $expenseCategory] = setUpTransactionBulkWorkspace();

    $transaction = app(RecordIncomeExpense::class)->record(
        $account,
        $expenseCategory,
        TransactionType::Expense,
        2500,
        'Coffee',
        now(),
        $user,
    );

    $outsider = User::factory()->create();
    app(CreatePersonalWorkspace::class)->create($outsider);

    $this->actingAs($outsider)
        ->post(route('transactions.bulk-duplicate'), [
            'transaction_ids' => [$transaction->id],
        ])
        ->assertRedirect(route('transactions.index'));

    expect(Transaction::query()->where('status', TransactionStatus::Draft)->count())->toBe(0);
});

test('bulk duplicate requires at least one transaction id', function () {
    [$user] = setUpTransactionBulkWorkspace();

    $this->actingAs($user)
        ->post(route('transactions.bulk-duplicate'), ['transaction_ids' => []])
        ->assertInvalid(['transaction_ids']);
});
