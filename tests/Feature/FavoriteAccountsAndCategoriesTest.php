<?php

use App\Domain\Workspaces\CreatePersonalWorkspace;
use App\Models\Account;
use App\Models\Category;
use App\Models\User;
use Database\Seeders\CurrencySeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(CurrencySeeder::class);
});

function setUpFavoritesWorkspace(): array
{
    $user = User::factory()->create();
    $workspace = app(CreatePersonalWorkspace::class)->create($user);

    return [$user, $workspace];
}

test('an account can be marked and unmarked as a favorite', function () {
    [$user, $workspace] = setUpFavoritesWorkspace();
    $account = Account::factory()->for($workspace)->create();

    $this->actingAs($user)->patch(route('accounts.update', $account), [
        'name' => $account->name,
        'type' => $account->type->value,
        'currency_code' => $account->currency_code,
        'is_favorite' => true,
    ])->assertRedirect(route('accounts.index'));

    expect($account->fresh())->is_favorite->toBeTrue();

    $this->actingAs($user)->patch(route('accounts.update', $account), [
        'name' => $account->name,
        'type' => $account->type->value,
        'currency_code' => $account->currency_code,
        'is_favorite' => false,
    ])->assertRedirect(route('accounts.index'));

    expect($account->fresh())->is_favorite->toBeFalse();
});

test('a category can be marked as a favorite', function () {
    [$user, $workspace] = setUpFavoritesWorkspace();
    $category = Category::factory()->for($workspace)->expense()->create();

    $this->actingAs($user)->patch(route('categories.update', $category), [
        'name' => $category->name,
        'type' => $category->type->value,
        'is_favorite' => true,
    ])->assertRedirect(route('categories.index'));

    expect($category->fresh())->is_favorite->toBeTrue();
});

test('the entry form lists favorite accounts and categories first', function () {
    [$user, $workspace] = setUpFavoritesWorkspace();

    $regularAccount = Account::factory()->for($workspace)->create(['name' => 'Zebra account']);
    $favoriteAccount = Account::factory()->for($workspace)->favorite()->create(['name' => 'Apple account']);

    $regularCategory = Category::factory()->for($workspace)->expense()->create(['name' => 'Zebra category']);
    $favoriteCategory = Category::factory()->for($workspace)->expense()->favorite()->create(['name' => 'Apple category']);

    $this->actingAs($user)
        ->get(route('transactions.create'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transactions/CreateTransaction')
            ->where('accounts.0.id', $favoriteAccount->id)
            ->where('accounts.0.is_favorite', true)
            ->where('accounts.1.id', $regularAccount->id)
            ->where('accounts.1.is_favorite', false)
            ->where('categories.0.id', $favoriteCategory->id)
            ->where('categories.0.is_favorite', true)
            ->where('categories.1.id', $regularCategory->id)
            ->where('categories.1.is_favorite', false));
});
