<?php

use App\Domain\Workspaces\CreatePersonalWorkspace;
use App\Enums\CategoryType;
use App\Models\Account;
use App\Models\AccountGroup;
use App\Models\Category;
use App\Models\User;
use Database\Seeders\CurrencySeeder;

beforeEach(function () {
    $this->seed(CurrencySeeder::class);
});

function createOrderingWorkspace(): array
{
    $user = User::factory()->create();
    $workspace = app(CreatePersonalWorkspace::class)->create($user);

    return [$user, $workspace];
}

test('account groups can move within the current workspace', function () {
    [$user, $workspace] = createOrderingWorkspace();
    [, $otherWorkspace] = createOrderingWorkspace();
    $first = AccountGroup::factory()->for($workspace)->create(['position' => 0]);
    $second = AccountGroup::factory()->for($workspace)->create(['position' => 10]);
    $other = AccountGroup::factory()->for($otherWorkspace)->create(['position' => 5]);

    $this->actingAs($user)
        ->patch(route('account-groups.move', [$second, 'direction' => 'up']))
        ->assertRedirect(route('accounts.index'));

    expect($first->fresh()->position)->toBe(10)
        ->and($second->fresh()->position)->toBe(0)
        ->and($other->fresh()->position)->toBe(5);
});

test('accounts only move within their current group', function () {
    [$user, $workspace] = createOrderingWorkspace();
    $group = AccountGroup::factory()->for($workspace)->create();
    $otherGroup = AccountGroup::factory()->for($workspace)->create();
    $first = Account::factory()->for($workspace)->inGroup($group)->create(['position' => 0]);
    $second = Account::factory()->for($workspace)->inGroup($group)->create(['position' => 10]);
    $other = Account::factory()->for($workspace)->inGroup($otherGroup)->create(['position' => 5]);

    $this->actingAs($user)
        ->patch(route('accounts.move', $second), ['direction' => 'up'])
        ->assertRedirect(route('accounts.index'));

    expect($first->fresh()->position)->toBe(10)
        ->and($second->fresh()->position)->toBe(0)
        ->and($other->fresh()->position)->toBe(5);
});

test('categories only move among matching type and parent siblings', function () {
    [$user, $workspace] = createOrderingWorkspace();
    $parent = Category::factory()->for($workspace)->expense()->create();
    $otherParent = Category::factory()->for($workspace)->expense()->create();
    $first = Category::factory()->subcategoryOf($parent)->create(['position' => 0]);
    $second = Category::factory()->subcategoryOf($parent)->create(['position' => 10]);
    $otherChild = Category::factory()->subcategoryOf($otherParent)->create(['position' => 5]);
    $income = Category::factory()->for($workspace)->income()->create(['position' => 5]);

    $this->actingAs($user)
        ->patch(route('categories.move', $second), ['direction' => 'up'])
        ->assertRedirect(route('categories.index'));

    expect($first->fresh()->position)->toBe(10)
        ->and($second->fresh()->position)->toBe(0)
        ->and($otherChild->fresh()->position)->toBe(5)
        ->and($income->fresh()->position)->toBe(5)
        ->and($second->fresh()->type)->toBe(CategoryType::Expense);
});

test('resources moved to a new sibling scope are placed at the end', function () {
    [$user, $workspace] = createOrderingWorkspace();
    $firstGroup = AccountGroup::factory()->for($workspace)->create();
    $secondGroup = AccountGroup::factory()->for($workspace)->create();
    $account = Account::factory()->for($workspace)->inGroup($firstGroup)->create(['position' => 0]);
    Account::factory()->for($workspace)->inGroup($secondGroup)->create(['position' => 8]);
    $expenseParent = Category::factory()->for($workspace)->expense()->create();
    $incomeParent = Category::factory()->for($workspace)->income()->create();
    $category = Category::factory()->subcategoryOf($expenseParent)->create(['position' => 0]);
    Category::factory()->subcategoryOf($incomeParent)->create(['position' => 8]);

    $this->actingAs($user)->patch(route('accounts.update', $account), [
        'name' => $account->name,
        'type' => $account->type->value,
        'currency_code' => $account->currency_code,
        'account_group_id' => $secondGroup->id,
        'is_visible' => $account->is_visible,
        'include_in_total' => $account->include_in_total,
    ])->assertRedirect(route('accounts.index'));

    $this->actingAs($user)->patch(route('categories.update', $category), [
        'name' => $category->name,
        'type' => CategoryType::Income->value,
        'parent_id' => $incomeParent->id,
        'is_visible' => $category->is_visible,
    ])->assertRedirect(route('categories.index'));

    expect($account->fresh())
        ->account_group_id->toBe($secondGroup->id)
        ->position->toBe(9)
        ->and($category->fresh())
        ->parent_id->toBe($incomeParent->id)
        ->type->toBe(CategoryType::Income)
        ->position->toBe(9);
});

test('ordering validates direction and forbids cross workspace changes', function () {
    [$user, $workspace] = createOrderingWorkspace();
    [, $otherWorkspace] = createOrderingWorkspace();
    $group = AccountGroup::factory()->for($workspace)->create(['position' => 0]);
    $otherGroup = AccountGroup::factory()->for($otherWorkspace)->create();
    $otherAccount = Account::factory()->for($otherWorkspace)->create();
    $otherCategory = Category::factory()->for($otherWorkspace)->create();

    $this->actingAs($user)
        ->patch(route('account-groups.move', $group), ['direction' => 'sideways'])
        ->assertSessionHasErrors('direction');

    $this->actingAs($user)
        ->patch(route('account-groups.move', $otherGroup), ['direction' => 'up'])
        ->assertForbidden();

    $this->actingAs($user)
        ->patch(route('accounts.move', $otherAccount), ['direction' => 'up'])
        ->assertForbidden();

    $this->actingAs($user)
        ->patch(route('categories.move', $otherCategory), ['direction' => 'up'])
        ->assertForbidden();

    expect($group->fresh()->position)->toBe(0);
});
