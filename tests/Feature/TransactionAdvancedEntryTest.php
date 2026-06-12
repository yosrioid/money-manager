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

    $this->actingAs($user)
        ->post(route('transactions.drafts.store'), [
            'type' => 'expense',
            'account_id' => $account->id,
            'amount' => '1000 + 250',
            'memo' => 'Finish categorizing later',
        ])
        ->assertRedirect(route('accounts.index'));

    $draft = Transaction::query()->sole();

    expect($draft->status)->toBe(TransactionStatus::Draft)
        ->and($draft->entries()->count())->toBe(0)
        ->and($draft->draft_data['amount'])->toBe('1000 + 250')
        ->and(app(CalculateAccountBalance::class)->calculate($account->fresh()))->toBe(0);
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

    $this->actingAs($user)
        ->post(route('transactions.duplicate', $original))
        ->assertRedirect(route('accounts.index'));

    $draft = Transaction::query()->where('status', TransactionStatus::Draft)->sole();

    expect($draft->entries()->count())->toBe(0)
        ->and($draft->draft_data['source_transaction_id'])->toBe($original->id)
        ->and($draft->draft_data['category_id'])->toBe($category->id)
        ->and(app(CalculateAccountBalance::class)->calculate($account->fresh()))->toBe(-7500);
});
