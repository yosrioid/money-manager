<?php

use App\Domain\Transactions\RecordIncomeExpense;
use App\Domain\Workspaces\CreatePersonalWorkspace;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Merchant;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function setUpTransactionRecentValuesWorkspace(): array
{
    $user = User::factory()->create();
    $workspace = app(CreatePersonalWorkspace::class)->create($user);
    $account = Account::factory()->for($workspace)->create();
    $category = Category::factory()->for($workspace)->expense()->create();

    return [$user, $workspace, $account, $category];
}

test('the entry form suggests recently used descriptions and merchants', function () {
    [$user, $workspace, $account, $category] = setUpTransactionRecentValuesWorkspace();

    $coffeeShop = Merchant::factory()->for($workspace)->create(['name' => 'Coffee Shop']);
    $groceryStore = Merchant::factory()->for($workspace)->create(['name' => 'Grocery Store']);

    app(RecordIncomeExpense::class)->record(
        $account,
        $category,
        TransactionType::Expense,
        1000,
        'Groceries',
        now()->subDays(2),
        $user,
        $groceryStore,
    );

    app(RecordIncomeExpense::class)->record(
        $account,
        $category,
        TransactionType::Expense,
        500,
        'Coffee',
        now()->subDay(),
        $user,
        $coffeeShop,
    );

    app(RecordIncomeExpense::class)->record(
        $account,
        $category,
        TransactionType::Expense,
        500,
        'Coffee',
        now(),
        $user,
        $coffeeShop,
    );

    $this->actingAs($user)
        ->get(route('transactions.create'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transactions/CreateTransaction')
            ->where('recentDescriptions', ['Coffee', 'Groceries'])
            ->where('recentMerchants.0.id', $coffeeShop->id)
            ->where('recentMerchants.1.id', $groceryStore->id));
});

test('draft and unposted transactions do not contribute to recent values', function () {
    [$user, $workspace, $account, $category] = setUpTransactionRecentValuesWorkspace();

    $this->actingAs($user)->post(route('transactions.drafts.store'), [
        'type' => 'expense',
        'account_id' => $account->id,
        'category_id' => $category->id,
        'amount' => '1000',
        'description' => 'Draft only purchase',
    ]);

    $this->actingAs($user)
        ->get(route('transactions.create'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transactions/CreateTransaction')
            ->where('recentDescriptions', [])
            ->where('recentMerchants', []));
});
