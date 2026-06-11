<?php

use App\Domain\Accounts\CreateAccountWithOpeningBalance;
use App\Domain\Workspaces\CreatePersonalWorkspace;
use App\Enums\AccountType;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\CurrencySeeder;

beforeEach(function () {
    $this->seed(CurrencySeeder::class);

    $this->user = User::factory()->create();
    $this->workspace = app(CreatePersonalWorkspace::class)->create($this->user);

    $account = app(CreateAccountWithOpeningBalance::class)->create($this->workspace, [
        'name' => 'Lifecycle account',
        'type' => AccountType::Cash,
        'currency_code' => 'IDR',
        'opening_balance' => 1000,
    ], $this->user);

    $this->transaction = $account->ledgerEntries()->sole()->transaction;
});

test('a posted transaction can transition to reversed', function () {
    $this->transaction->update(['status' => TransactionStatus::Reversed]);

    expect($this->transaction->fresh()->status)->toBe(TransactionStatus::Reversed);
});

test('a posted transaction can transition to replaced', function () {
    $this->transaction->update(['status' => TransactionStatus::Replaced]);

    expect($this->transaction->fresh()->status)->toBe(TransactionStatus::Replaced);
});

test('a posted transaction cannot transition to voided', function () {
    expect(fn () => $this->transaction->update(['status' => TransactionStatus::Voided]))
        ->toThrow(LogicException::class);
});

test('a posted transaction status change cannot include other field changes', function () {
    expect(fn () => $this->transaction->forceFill([
        'status' => TransactionStatus::Reversed,
        'description' => 'Changed',
    ])->save())->toThrow(LogicException::class);
});

test('terminal status transactions are immutable', function (TransactionStatus $status) {
    $this->transaction->update(['status' => $status]);

    expect(fn () => $this->transaction->fresh()->update(['status' => TransactionStatus::Posted]))
        ->toThrow(LogicException::class)
        ->and(fn () => $this->transaction->fresh()->update(['description' => 'Changed']))
        ->toThrow(LogicException::class);
})->with([
    TransactionStatus::Reversed,
    TransactionStatus::Replaced,
]);

test('a draft transaction can be voided', function () {
    $draft = Transaction::query()->create([
        'workspace_id' => $this->workspace->id,
        'created_by' => $this->user->id,
        'type' => TransactionType::OpeningBalance,
        'status' => TransactionStatus::Draft,
        'currency_code' => 'IDR',
        'description' => 'Draft transaction',
        'occurred_at' => now(),
        'posted_at' => null,
    ]);

    $draft->update(['status' => TransactionStatus::Voided]);

    expect($draft->fresh()->status)->toBe(TransactionStatus::Voided)
        ->and(fn () => $draft->fresh()->update(['description' => 'Changed']))
        ->toThrow(LogicException::class);
});

test('voiding a draft transaction cannot include other field changes', function () {
    $draft = Transaction::query()->create([
        'workspace_id' => $this->workspace->id,
        'created_by' => $this->user->id,
        'type' => TransactionType::OpeningBalance,
        'status' => TransactionStatus::Draft,
        'currency_code' => 'IDR',
        'description' => 'Draft transaction',
        'occurred_at' => now(),
        'posted_at' => null,
    ]);

    expect(fn () => $draft->forceFill([
        'status' => TransactionStatus::Voided,
        'description' => 'Changed',
    ])->save())->toThrow(LogicException::class);
});
