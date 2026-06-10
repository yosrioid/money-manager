<?php

use App\Domain\Workspaces\CreatePersonalWorkspace;
use App\Enums\CategoryType;
use App\Models\Category;
use App\Models\Merchant;
use App\Models\Tag;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function createReferenceDataWorkspace(): array
{
    $user = User::factory()->create();
    $workspace = app(CreatePersonalWorkspace::class)->create($user);

    return [$user, $workspace];
}

test('merchant and tag pages only display active current workspace resources', function () {
    [$user, $workspace] = createReferenceDataWorkspace();
    [, $otherWorkspace] = createReferenceDataWorkspace();

    Merchant::factory()->for($workspace)->create();
    Merchant::factory()->for($workspace)->archived()->create();
    Merchant::factory()->for($otherWorkspace)->create();
    Tag::factory()->for($workspace)->create();
    Tag::factory()->for($workspace)->archived()->create();
    Tag::factory()->for($otherWorkspace)->create();

    $this->actingAs($user)
        ->get(route('merchants.index'))
        ->assertInertia(fn (Assert $page) => $page->component('merchants/Index')->has('merchants', 1));

    $this->actingAs($user)
        ->get(route('tags.index'))
        ->assertInertia(fn (Assert $page) => $page->component('tags/Index')->has('tags', 1));
});

test('user can create merchant and tag in current workspace', function () {
    [$user, $workspace] = createReferenceDataWorkspace();
    $category = Category::factory()->for($workspace)->create(['type' => CategoryType::Expense]);

    $this->actingAs($user)->post(route('merchants.store'), [
        'name' => 'Local Market',
        'default_category_id' => $category->id,
    ])->assertRedirect(route('merchants.index'));

    $this->actingAs($user)->post(route('tags.store'), [
        'name' => 'Family',
        'color' => '#3366FF',
    ])->assertRedirect(route('tags.index'));

    expect($workspace->merchants()->sole())
        ->name->toBe('Local Market')
        ->default_category_id->toBe($category->id)
        ->and($workspace->tags()->sole())
        ->name->toBe('Family')
        ->color->toBe('#3366FF');
});

test('merchant cannot reference category from another workspace', function () {
    [$user] = createReferenceDataWorkspace();
    [, $otherWorkspace] = createReferenceDataWorkspace();
    $category = Category::factory()->for($otherWorkspace)->create();

    $this->actingAs($user)->post(route('merchants.store'), [
        'name' => 'Invalid Merchant',
        'default_category_id' => $category->id,
    ])->assertSessionHasErrors('default_category_id');
});

test('cross workspace changes are forbidden and deletes archive records', function () {
    [$user, $workspace] = createReferenceDataWorkspace();
    [, $otherWorkspace] = createReferenceDataWorkspace();
    $merchant = Merchant::factory()->for($workspace)->create();
    $tag = Tag::factory()->for($workspace)->create();
    $otherMerchant = Merchant::factory()->for($otherWorkspace)->create();
    $otherTag = Tag::factory()->for($otherWorkspace)->create();

    $this->actingAs($user)->patch(route('merchants.update', $otherMerchant), ['name' => 'Changed'])
        ->assertForbidden();
    $this->actingAs($user)->delete(route('tags.destroy', $otherTag))->assertForbidden();

    $this->actingAs($user)->delete(route('merchants.destroy', $merchant));
    $this->actingAs($user)->delete(route('tags.destroy', $tag));

    expect($merchant->fresh()->archived_at)->not->toBeNull()
        ->and($tag->fresh()->archived_at)->not->toBeNull();
});
