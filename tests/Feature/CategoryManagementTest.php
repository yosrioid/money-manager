<?php

use App\Domain\Workspaces\CreatePersonalWorkspace;
use App\Enums\CategoryType;
use App\Models\Category;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function createCategoryWorkspace(): array
{
    $user = User::factory()->create();
    $workspace = app(CreatePersonalWorkspace::class)->create($user);

    return [$user, $workspace];
}

test('categories page displays active categories from current workspace', function () {
    [$user, $workspace] = createCategoryWorkspace();
    [, $otherWorkspace] = createCategoryWorkspace();
    $parent = Category::factory()->for($workspace)->expense()->create();
    Category::factory()->subcategoryOf($parent)->create();
    Category::factory()->for($workspace)->archived()->create();
    Category::factory()->for($otherWorkspace)->create();

    $this->actingAs($user)->get(route('categories.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('categories/Index')
            ->has('expenseCategories', 1)
            ->has('expenseCategories.0.subcategories', 1),
        );
});

test('user can create a category and matching subcategory', function () {
    [$user, $workspace] = createCategoryWorkspace();
    $parent = Category::factory()->for($workspace)->expense()->create();

    $this->actingAs($user)->post(route('categories.store'), [
        'name' => 'Coffee',
        'type' => CategoryType::Expense->value,
        'parent_id' => $parent->id,
        'color' => '#AA5500',
        'icon' => 'coffee',
        'is_visible' => true,
    ])->assertRedirect(route('categories.index'));

    expect($workspace->categories()->where('name', 'Coffee')->sole())
        ->parent_id->toBe($parent->id)
        ->type->toBe(CategoryType::Expense);
});

test('category parent must be a matching top-level category in current workspace', function () {
    [$user, $workspace] = createCategoryWorkspace();
    [, $otherWorkspace] = createCategoryWorkspace();
    $otherParent = Category::factory()->for($otherWorkspace)->expense()->create();
    $incomeParent = Category::factory()->for($workspace)->income()->create();

    foreach ([$otherParent, $incomeParent] as $parent) {
        $this->actingAs($user)->post(route('categories.store'), [
            'name' => 'Invalid',
            'type' => CategoryType::Expense->value,
            'parent_id' => $parent->id,
        ])->assertSessionHasErrors('parent_id');
    }
});

test('cross workspace changes are forbidden and leaf categories are archived', function () {
    [$user, $workspace] = createCategoryWorkspace();
    [, $otherWorkspace] = createCategoryWorkspace();
    $category = Category::factory()->for($workspace)->create();
    $otherCategory = Category::factory()->for($otherWorkspace)->create();

    $this->actingAs($user)->patch(route('categories.update', $otherCategory), [
        'name' => 'Changed',
        'type' => $otherCategory->type->value,
    ])->assertForbidden();

    $this->actingAs($user)->delete(route('categories.destroy', $category))
        ->assertRedirect(route('categories.index'));

    expect($category->fresh()->archived_at)->not->toBeNull();
});
