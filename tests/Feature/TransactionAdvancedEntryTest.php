<?php

use App\Domain\Ledger\CalculateAccountBalance;
use App\Domain\Transactions\EvaluateAmountExpression;
use App\Domain\Workspaces\CreatePersonalWorkspace;
use App\Enums\LedgerEntryType;
use App\Enums\TransactionStatus;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function createAdvancedTransactionWorkspace(): array
{
    $user = User::factory()->create();
    $workspace = app(CreatePersonalWorkspace::class)->create($user);

    return [$user, $workspace];
}

test('safe amount expressions respect arithmetic precedence', function () {
    $evaluator = app(EvaluateAmountExpression::class);

    expect($evaluator->evaluate('1000 + 250 * 2'))->toBe(1500)
        ->and($evaluator->evaluate('(1000 + 250) * 2'))->toBe(2500)
        ->and(fn () => $evaluator->evaluate('100 / 3'))->toThrow(LogicException::class)
        ->and(fn () => $evaluator->evaluate('phpinfo()'))->toThrow(LogicException::class);
});

test('user can record a split expense using calculator expressions', function () {
    [$user, $workspace] = createAdvancedTransactionWorkspace();
    $account = Account::factory()->for($workspace)->create();
    $food = Category::factory()->for($workspace)->expense()->create();
    $transport = Category::factory()->for($workspace)->expense()->create();

    $this->actingAs($user)
        ->post(route('transactions.store'), [
            'type' => 'expense',
            'account_id' => $account->id,
            'amount' => '10000 + 5000',
            'splits' => [
                ['category_id' => $food->id, 'amount' => '5000 * 2'],
                ['category_id' => $transport->id, 'amount' => '5000'],
            ],
            'description' => 'Split purchase',
            'occurred_at' => now()->toDateTimeString(),
        ])
        ->assertRedirect(route('accounts.index'));

    $transaction = Transaction::query()->sole();

    expect($transaction->entries()->where('type', LedgerEntryType::Category)->count())->toBe(2)
        ->and($transaction->entries()->sum('amount'))->toBe(0)
        ->and(app(CalculateAccountBalance::class)->calculate($account->fresh()))->toBe(-15000);
});

test('split amounts must equal the total amount', function () {
    [$user, $workspace] = createAdvancedTransactionWorkspace();
    $account = Account::factory()->for($workspace)->create();
    $food = Category::factory()->for($workspace)->expense()->create();
    $transport = Category::factory()->for($workspace)->expense()->create();

    $this->actingAs($user)
        ->post(route('transactions.store'), [
            'type' => 'expense',
            'account_id' => $account->id,
            'amount' => '15000',
            'splits' => [
                ['category_id' => $food->id, 'amount' => '5000'],
                ['category_id' => $transport->id, 'amount' => '5000'],
            ],
            'description' => 'Invalid split',
            'occurred_at' => now()->toDateTimeString(),
        ])
        ->assertSessionHasErrors('splits');

    expect(Transaction::query()->count())->toBe(0);
});

test('incomplete draft can be saved without affecting balances', function () {
    [$user, $workspace] = createAdvancedTransactionWorkspace();
    $account = Account::factory()->for($workspace)->create();

    $response = $this->actingAs($user)
        ->post(route('transactions.drafts.store'), [
            'type' => 'expense',
            'account_id' => $account->id,
            'amount' => '1000 + 250',
            'memo' => 'Finish categorizing later',
        ]);

    $draft = Transaction::query()->sole();
    $response->assertRedirect(route('transactions.drafts.edit', $draft));

    expect($draft->status)->toBe(TransactionStatus::Draft)
        ->and($draft->entries()->count())->toBe(0)
        ->and($draft->draft_data['amount'])->toBe('1000 + 250')
        ->and(app(CalculateAccountBalance::class)->calculate($account->fresh()))->toBe(0);

    $this->get(route('transactions.drafts.edit', $draft))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transactions/CreateTransaction')
            ->where('draftId', $draft->id)
            ->where('initialData.amount', '1000 + 250')
            ->where('initialData.memo', 'Finish categorizing later'));
});

test('saved draft can be updated and posted without retaining a stale active draft', function () {
    [$user, $workspace] = createAdvancedTransactionWorkspace();
    $account = Account::factory()->for($workspace)->create();
    $category = Category::factory()->for($workspace)->expense()->create();

    $this->actingAs($user)->post(route('transactions.drafts.store'), [
        'type' => 'expense',
        'account_id' => $account->id,
        'amount' => '1000',
        'description' => 'Initial draft',
    ]);

    $draft = Transaction::query()->sole();

    $this->patch(route('transactions.drafts.update', $draft), [
        'type' => 'expense',
        'account_id' => $account->id,
        'category_id' => $category->id,
        'amount' => '2500',
        'description' => 'Updated draft',
        'occurred_at' => now()->toDateTimeString(),
    ])->assertRedirect(route('transactions.drafts.edit', $draft));

    expect($draft->fresh()->draft_data['amount'])->toBe('2500')
        ->and($draft->fresh()->description)->toBe('Updated draft');

    $this->post(route('transactions.store'), [
        'draft_id' => $draft->id,
        'type' => 'expense',
        'account_id' => $account->id,
        'category_id' => $category->id,
        'amount' => '2500',
        'description' => 'Updated draft',
        'occurred_at' => now()->toDateTimeString(),
    ])->assertRedirect(route('accounts.index'));

    expect($draft->fresh()->status)->toBe(TransactionStatus::Voided)
        ->and(Transaction::query()->where('status', TransactionStatus::Posted)->count())->toBe(1)
        ->and(app(CalculateAccountBalance::class)->calculate($account->fresh()))->toBe(-2500);

    $this->get(route('transactions.drafts.edit', $draft))->assertNotFound();
});

test('draft requests reject archived financial references', function () {
    [$user, $workspace] = createAdvancedTransactionWorkspace();
    $archivedAccount = Account::factory()->for($workspace)->create(['archived_at' => now()]);
    $archivedCategory = Category::factory()->for($workspace)->expense()->create(['archived_at' => now()]);

    $this->actingAs($user)
        ->post(route('transactions.drafts.store'), [
            'type' => 'expense',
            'account_id' => $archivedAccount->id,
            'category_id' => $archivedCategory->id,
            'amount' => '1000',
        ])
        ->assertSessionHasErrors(['account_id', 'category_id']);

    expect(Transaction::query()->count())->toBe(0);
});

test('draft resume and update deny another workspace', function () {
    [$owner, $workspace] = createAdvancedTransactionWorkspace();
    [$outsider] = createAdvancedTransactionWorkspace();
    $account = Account::factory()->for($workspace)->create();

    $this->actingAs($owner)->post(route('transactions.drafts.store'), [
        'type' => 'expense',
        'account_id' => $account->id,
        'amount' => '1000',
    ]);

    $draft = Transaction::query()->where('workspace_id', $workspace->id)->sole();

    $this->actingAs($outsider)
        ->get(route('transactions.drafts.edit', $draft))
        ->assertNotFound();

    $this->patch(route('transactions.drafts.update', $draft), [
        'type' => 'expense',
        'amount' => '2500',
    ])->assertNotFound();

    expect($draft->fresh()->draft_data['amount'])->toBe('1000');
});

test('posted transaction can be duplicated into a balance neutral draft', function () {
    [$user, $workspace] = createAdvancedTransactionWorkspace();
    $account = Account::factory()->for($workspace)->create();
    $category = Category::factory()->for($workspace)->expense()->create();

    $this->actingAs($user)->post(route('transactions.store'), [
        'type' => 'expense',
        'account_id' => $account->id,
        'category_id' => $category->id,
        'amount' => '7500',
        'description' => 'Original',
        'occurred_at' => now()->toDateTimeString(),
    ]);

    $original = Transaction::query()->where('status', TransactionStatus::Posted)->sole();

    $response = $this->actingAs($user)
        ->post(route('transactions.duplicate', $original));

    $draft = Transaction::query()->where('status', TransactionStatus::Draft)->sole();
    $response->assertRedirect(route('transactions.drafts.edit', $draft));

    expect($draft->entries()->count())->toBe(0)
        ->and($draft->draft_data['source_transaction_id'])->toBe($original->id)
        ->and($draft->draft_data['category_id'])->toBe($category->id)
        ->and(app(CalculateAccountBalance::class)->calculate($account->fresh()))->toBe(-7500);
});
