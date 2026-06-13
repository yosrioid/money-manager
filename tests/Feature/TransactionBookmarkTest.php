<?php

use App\Domain\Workspaces\CreatePersonalWorkspace;
use App\Models\Account;
use App\Models\TransactionBookmark;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function setUpTransactionBookmarkWorkspace(): array
{
    $user = User::factory()->create();
    $workspace = app(CreatePersonalWorkspace::class)->create($user);

    return [$user, $workspace];
}

test('a transaction can be saved as a bookmark', function () {
    [$user, $workspace] = setUpTransactionBookmarkWorkspace();
    $account = Account::factory()->for($workspace)->create();

    $response = $this->actingAs($user)->post(route('transaction-bookmarks.store'), [
        'name' => 'Weekly groceries',
        'type' => 'expense',
        'account_id' => $account->id,
        'amount' => '5000',
        'description' => 'Groceries',
    ]);

    $bookmark = TransactionBookmark::query()->sole();
    $response->assertRedirect();

    expect($bookmark->workspace_id)->toBe($workspace->id)
        ->and($bookmark->name)->toBe('Weekly groceries')
        ->and($bookmark->position)->toBe(0)
        ->and($bookmark->payload['amount'])->toBe('5000')
        ->and($bookmark->payload['account_id'])->toBe($account->id)
        ->and($bookmark->payload)->not->toHaveKey('name');
});

test('bookmarks are listed on the create transaction page and can prefill the form', function () {
    [$user, $workspace] = setUpTransactionBookmarkWorkspace();
    $account = Account::factory()->for($workspace)->create();

    $bookmark = TransactionBookmark::factory()->for($workspace)->create([
        'name' => 'Weekly groceries',
        'payload' => ['type' => 'expense', 'account_id' => $account->id, 'amount' => '5000', 'description' => 'Groceries'],
        'position' => 0,
    ]);

    $this->actingAs($user)
        ->get(route('transactions.create'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transactions/CreateTransaction')
            ->where('bookmarks.0.id', $bookmark->id)
            ->where('bookmarks.0.name', 'Weekly groceries'));

    $this->actingAs($user)
        ->get(route('transactions.create', ['bookmark_id' => $bookmark->id]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transactions/CreateTransaction')
            ->where('initialData.amount', '5000')
            ->where('initialData.description', 'Groceries'));
});

test('a bookmark can be renamed', function () {
    [$user, $workspace] = setUpTransactionBookmarkWorkspace();

    $bookmark = TransactionBookmark::factory()->for($workspace)->create(['name' => 'Old name']);

    $this->actingAs($user)
        ->patch(route('transaction-bookmarks.update', $bookmark), ['name' => 'New name'])
        ->assertRedirect();

    expect($bookmark->fresh()->name)->toBe('New name');
});

test('bookmarks can be reordered', function () {
    [$user, $workspace] = setUpTransactionBookmarkWorkspace();

    $first = TransactionBookmark::factory()->for($workspace)->create(['position' => 0]);
    $second = TransactionBookmark::factory()->for($workspace)->create(['position' => 1]);

    $this->actingAs($user)
        ->patch(route('transaction-bookmarks.move', $first), ['direction' => 'down'])
        ->assertRedirect();

    expect($first->fresh()->position)->toBe(1)
        ->and($second->fresh()->position)->toBe(0);
});

test('a bookmark can be deleted', function () {
    [$user, $workspace] = setUpTransactionBookmarkWorkspace();

    $bookmark = TransactionBookmark::factory()->for($workspace)->create();

    $this->actingAs($user)
        ->delete(route('transaction-bookmarks.destroy', $bookmark))
        ->assertRedirect();

    expect(TransactionBookmark::query()->count())->toBe(0);
});

test('a bookmark belonging to another workspace cannot be renamed, moved, or deleted', function () {
    [$user] = setUpTransactionBookmarkWorkspace();
    $outsider = User::factory()->create();
    $outsiderWorkspace = app(CreatePersonalWorkspace::class)->create($outsider);

    $bookmark = TransactionBookmark::factory()->for($outsiderWorkspace)->create(['name' => 'Outsider bookmark']);

    $this->actingAs($user)
        ->patch(route('transaction-bookmarks.update', $bookmark), ['name' => 'Hijacked'])
        ->assertForbidden();

    $this->actingAs($user)
        ->patch(route('transaction-bookmarks.move', $bookmark), ['direction' => 'down'])
        ->assertForbidden();

    $this->actingAs($user)
        ->delete(route('transaction-bookmarks.destroy', $bookmark))
        ->assertForbidden();

    expect($bookmark->fresh()->name)->toBe('Outsider bookmark');
});
