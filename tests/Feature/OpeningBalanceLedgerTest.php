<?php

use App\Domain\Accounts\CreateAccountWithOpeningBalance;
use App\Domain\Ledger\CalculateAccountBalance;
use App\Domain\Workspaces\CreatePersonalWorkspace;
use App\Enums\AccountType;
use App\Enums\LedgerEntryType;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\CurrencySeeder;
use Illuminate\Auth\Access\AuthorizationException;

beforeEach(function () {
    $this->seed(CurrencySeeder::class);
});

test('account opening balance is posted as a balanced immutable ledger transaction', function () {
    $user = User::factory()->create();
    $workspace = app(CreatePersonalWorkspace::class)->create($user);

    $this->actingAs($user)->post(route('accounts.store'), [
        'name' => 'Opening account',
        'type' => AccountType::BankAccount->value,
        'currency_code' => 'IDR',
        'opening_balance' => 125000,
        'account_group_id' => null,
        'is_visible' => true,
        'include_in_total' => true,
    ])->assertRedirect(route('accounts.index'));

    $account = $workspace->accounts()->sole();
    $transaction = $workspace->transactions()->with('entries')->sole();

    expect($transaction)
        ->type->toBe(TransactionType::OpeningBalance)
        ->status->toBe(TransactionStatus::Posted)
        ->currency_code->toBe('IDR')
        ->and($transaction->entries)->toHaveCount(2)
        ->and($transaction->entries->sum('amount'))->toBe(0)
        ->and($transaction->entries->where('type', LedgerEntryType::Account)->sole()->amount)->toBe(125000)
        ->and($transaction->entries->where('type', LedgerEntryType::OpeningBalanceEquity)->sole()->amount)->toBe(-125000)
        ->and(app(CalculateAccountBalance::class)->calculate($account))->toBe(125000);

    expect(fn () => $transaction->update(['description' => 'Changed']))
        ->toThrow(LogicException::class)
        ->and(fn () => $transaction->entries->firstOrFail()->delete())
        ->toThrow(LogicException::class)
        ->and(fn () => $transaction->entries()->create([
            'workspace_id' => $workspace->id,
            'account_id' => $account->id,
            'type' => LedgerEntryType::Account,
            'currency_code' => 'IDR',
            'amount' => 1,
        ]))
        ->toThrow(LogicException::class);
});

test('zero opening balance does not create an unnecessary ledger transaction', function () {
    $user = User::factory()->create();
    $workspace = app(CreatePersonalWorkspace::class)->create($user);

    $this->actingAs($user)->post(route('accounts.store'), [
        'name' => 'Zero account',
        'type' => AccountType::Cash->value,
        'currency_code' => 'IDR',
        'opening_balance' => 0,
    ])->assertRedirect(route('accounts.index'));

    expect($workspace->transactions()->count())->toBe(0)
        ->and(app(CalculateAccountBalance::class)->calculate($workspace->accounts()->sole()))->toBe(0);
});

test('account currency cannot change after an opening balance is posted', function () {
    $user = User::factory()->create();
    $workspace = app(CreatePersonalWorkspace::class)->create($user);
    $account = app(CreateAccountWithOpeningBalance::class)->create($workspace, [
        'name' => 'Locked currency',
        'type' => AccountType::BankAccount,
        'currency_code' => 'IDR',
        'opening_balance' => 1000,
    ], $user);

    $this->actingAs($user)->patch(route('accounts.update', $account), [
        'name' => $account->name,
        'type' => $account->type->value,
        'currency_code' => 'USD',
    ])->assertSessionHasErrors('currency_code');

    expect($account->fresh()->currency_code)->toBe('IDR')
        ->and(app(CalculateAccountBalance::class)->calculate($account))->toBe(1000);
});

test('account creation rejects an actor from another workspace atomically', function (int $openingBalance) {
    $owner = User::factory()->create();
    $workspace = app(CreatePersonalWorkspace::class)->create($owner);
    $otherUser = User::factory()->create();
    app(CreatePersonalWorkspace::class)->create($otherUser);

    expect(fn () => app(CreateAccountWithOpeningBalance::class)->create($workspace, [
        'name' => 'Unauthorized account',
        'type' => AccountType::Cash,
        'currency_code' => 'IDR',
        'opening_balance' => $openingBalance,
    ], $otherUser))->toThrow(AuthorizationException::class);

    expect($workspace->accounts()->count())->toBe(0)
        ->and(Transaction::query()->where('workspace_id', $workspace->id)->count())->toBe(0);
})->with([0, 5000]);
