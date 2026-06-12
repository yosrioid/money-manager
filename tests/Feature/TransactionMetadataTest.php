<?php

use App\Domain\Ledger\PostTransaction;
use App\Domain\Workspaces\CreatePersonalWorkspace;
use App\Enums\LedgerEntryType;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Merchant;
use App\Models\Tag;
use App\Models\Transaction;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function createMetadataWorkspace(array $attributes = []): array
{
    $user = User::factory()->create();
    $workspace = app(CreatePersonalWorkspace::class)->create($user);
    $workspace->update($attributes);

    return [$user, $workspace->fresh()];
}

function metadataTransactionPayload(Account $account, Category $category, array $overrides = []): array
{
    return array_merge([
        'type' => 'expense',
        'account_id' => $account->id,
        'category_id' => $category->id,
        'amount' => 25000,
        'description' => 'Team lunch',
        'occurred_at' => '2026-06-12T10:30',
    ], $overrides);
}

test('income and expense can record merchant memo tags and workspace local time', function () {
    [$user, $workspace] = createMetadataWorkspace(['timezone' => 'Asia/Jakarta']);
    $account = Account::factory()->for($workspace)->create();
    $category = Category::factory()->for($workspace)->expense()->create();
    $merchant = Merchant::factory()->for($workspace)->create();
    $tags = Tag::factory()->count(2)->for($workspace)->create();

    $this->actingAs($user)
        ->post(route('transactions.store'), metadataTransactionPayload($account, $category, [
            'merchant_id' => $merchant->id,
            'memo' => 'Discussed the next release.',
            'tag_ids' => $tags->modelKeys(),
        ]))
        ->assertRedirect(route('accounts.index'));

    $transaction = Transaction::query()->sole();

    expect($transaction)
        ->merchant_id->toBe($merchant->id)
        ->memo->toBe('Discussed the next release.')
        ->and($transaction->occurred_at->utc()->format('Y-m-d H:i'))->toBe('2026-06-12 03:30')
        ->and($transaction->tags()->pluck('tags.id')->all())->toEqualCanonicalizing($tags->modelKeys())
        ->and($transaction->tags()->pluck('transaction_tags.workspace_id')->unique()->all())->toBe([$workspace->id]);
});

test('transfer can record memo and tags but cannot reference a merchant', function () {
    [$user, $workspace] = createMetadataWorkspace();
    $sourceAccount = Account::factory()->for($workspace)->create();
    $destinationAccount = Account::factory()->for($workspace)->create();
    $merchant = Merchant::factory()->for($workspace)->create();
    $tag = Tag::factory()->for($workspace)->create();

    $this->actingAs($user)
        ->post(route('transactions.store'), [
            'type' => 'transfer',
            'account_id' => $sourceAccount->id,
            'destination_account_id' => $destinationAccount->id,
            'amount' => 10000,
            'description' => 'Move funds',
            'memo' => 'Reserve transfer',
            'tag_ids' => [$tag->id],
            'occurred_at' => now()->format('Y-m-d\TH:i'),
        ])
        ->assertRedirect(route('accounts.index'));

    $transaction = Transaction::query()->sole();

    expect($transaction)
        ->type->toBe(TransactionType::Transfer)
        ->merchant_id->toBeNull()
        ->memo->toBe('Reserve transfer')
        ->and($transaction->tags()->sole()->is($tag))->toBeTrue();

    $this->actingAs($user)
        ->post(route('transactions.store'), [
            'type' => 'transfer',
            'account_id' => $sourceAccount->id,
            'destination_account_id' => $destinationAccount->id,
            'amount' => 10000,
            'description' => 'Invalid merchant transfer',
            'merchant_id' => $merchant->id,
            'occurred_at' => now()->format('Y-m-d\TH:i'),
        ])
        ->assertSessionHasErrors('merchant_id');
});

test('merchant and tags must be active resources from the current workspace', function () {
    [$user, $workspace] = createMetadataWorkspace();
    [, $otherWorkspace] = createMetadataWorkspace();
    $account = Account::factory()->for($workspace)->create();
    $category = Category::factory()->for($workspace)->expense()->create();
    $archivedMerchant = Merchant::factory()->for($workspace)->archived()->create();
    $otherMerchant = Merchant::factory()->for($otherWorkspace)->create();
    $archivedTag = Tag::factory()->for($workspace)->archived()->create();
    $otherTag = Tag::factory()->for($otherWorkspace)->create();

    foreach ([
        [['merchant_id' => $archivedMerchant->id], 'merchant_id'],
        [['merchant_id' => $otherMerchant->id], 'merchant_id'],
        [['tag_ids' => [$archivedTag->id]], 'tag_ids.0'],
        [['tag_ids' => [$otherTag->id]], 'tag_ids.0'],
    ] as [$metadata, $errorKey]) {
        $this->actingAs($user)
            ->post(route('transactions.store'), metadataTransactionPayload($account, $category, $metadata))
            ->assertSessionHasErrors($errorKey);
    }

    expect(Transaction::query()->count())->toBe(0);
});

test('generic posting service rejects merchant and tags outside the workspace', function () {
    [$user, $workspace] = createMetadataWorkspace();
    [, $otherWorkspace] = createMetadataWorkspace();
    $account = Account::factory()->for($workspace)->create();
    $category = Category::factory()->for($workspace)->expense()->create();
    $otherMerchant = Merchant::factory()->for($otherWorkspace)->create();
    $otherTag = Tag::factory()->for($otherWorkspace)->create();
    $entries = [
        ['account_id' => $account->id, 'category_id' => null, 'type' => LedgerEntryType::Account, 'amount' => -1000],
        ['account_id' => null, 'category_id' => $category->id, 'type' => LedgerEntryType::Category, 'amount' => 1000],
    ];

    expect(fn () => app(PostTransaction::class)->post(
        $workspace,
        TransactionType::Expense,
        $account->currency_code,
        'Invalid merchant',
        now(),
        $entries,
        $user,
        $otherMerchant,
    ))->toThrow(LogicException::class);

    expect(fn () => app(PostTransaction::class)->post(
        $workspace,
        TransactionType::Expense,
        $account->currency_code,
        'Invalid tags',
        now(),
        $entries,
        $user,
        null,
        null,
        [$otherTag],
    ))->toThrow(LogicException::class);

    expect(Transaction::query()->count())->toBe(0);
});

test('transaction create page renders active metadata options and workspace timezone', function () {
    [$user, $workspace] = createMetadataWorkspace(['timezone' => 'Asia/Jakarta']);
    Merchant::factory()->for($workspace)->create();
    Merchant::factory()->for($workspace)->archived()->create();
    Tag::factory()->for($workspace)->create();
    Tag::factory()->for($workspace)->archived()->create();

    $this->actingAs($user)
        ->get(route('transactions.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transactions/CreateTransaction')
            ->has('merchants', 1)
            ->has('tags', 1)
            ->where('timezone', 'Asia/Jakarta'),
        );
});

test('posted transaction tags are immutable', function () {
    [$user, $workspace] = createMetadataWorkspace();
    $account = Account::factory()->for($workspace)->create();
    $category = Category::factory()->for($workspace)->expense()->create();
    $tag = Tag::factory()->for($workspace)->create();
    $otherTag = Tag::factory()->for($workspace)->create();

    $this->actingAs($user)
        ->post(route('transactions.store'), metadataTransactionPayload($account, $category, [
            'tag_ids' => [$tag->id],
        ]))
        ->assertRedirect(route('accounts.index'));

    $transaction = Transaction::query()->sole();

    expect(fn () => $transaction->tags()->attach($otherTag, ['workspace_id' => $workspace->id]))
        ->toThrow(LogicException::class)
        ->and(fn () => $transaction->tags()->detach($tag))
        ->toThrow(LogicException::class);
});
