<?php

use App\Domain\Accounts\CreateAccountWithOpeningBalance;
use App\Domain\Ledger\CalculateAccountBalance;
use App\Domain\Ledger\ReverseTransaction;
use App\Domain\Workspaces\CreatePersonalWorkspace;
use App\Enums\AccountType;
use App\Enums\AuditAction;
use App\Enums\TransactionStatus;
use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\CurrencySeeder;
use Illuminate\Auth\Access\AuthorizationException;

beforeEach(function () {
    $this->seed(CurrencySeeder::class);

    $this->user = User::factory()->create();
    $this->workspace = app(CreatePersonalWorkspace::class)->create($this->user);

    $this->account = app(CreateAccountWithOpeningBalance::class)->create($this->workspace, [
        'name' => 'Reversible account',
        'type' => AccountType::Cash,
        'currency_code' => 'IDR',
        'opening_balance' => 5000,
    ], $this->user);

    $this->transaction = $this->account->ledgerEntries()->sole()->transaction;
});

test('reversing a posted transaction creates a balanced reversal and marks the original as reversed', function () {
    $reversal = app(ReverseTransaction::class)->reverse($this->transaction, $this->user);

    expect($reversal->status)->toBe(TransactionStatus::Posted)
        ->and($reversal->reverses_transaction_id)->toBe($this->transaction->id)
        ->and($reversal->entries()->sum('amount'))->toBe(0)
        ->and($this->transaction->fresh()->status)->toBe(TransactionStatus::Reversed)
        ->and(app(CalculateAccountBalance::class)->calculate($this->account->fresh()))->toBe(0);

    foreach ($this->transaction->entries as $entry) {
        $reversedEntry = $reversal->entries()->where('account_id', $entry->account_id)->sole();
        expect($reversedEntry->amount)->toBe(-$entry->amount);
    }
});

test('reversing a non posted transaction throws', function () {
    app(ReverseTransaction::class)->reverse($this->transaction, $this->user);

    expect(fn () => app(ReverseTransaction::class)->reverse($this->transaction->fresh(), $this->user))
        ->toThrow(LogicException::class);
});

test('reversing a transaction as a non member throws and changes nothing', function () {
    $outsider = User::factory()->create();
    app(CreatePersonalWorkspace::class)->create($outsider);

    expect(fn () => app(ReverseTransaction::class)->reverse($this->transaction, $outsider))
        ->toThrow(AuthorizationException::class);

    expect($this->transaction->fresh()->status)->toBe(TransactionStatus::Posted)
        ->and($this->workspace->transactions()->count())->toBe(1);
});

test('reversing a transaction records an audit log entry', function () {
    $reversal = app(ReverseTransaction::class)->reverse($this->transaction, $this->user);

    $auditLog = AuditLog::query()->where('subject_id', $this->transaction->id)->sole();

    expect($auditLog->action)->toBe(AuditAction::TransactionReversed)
        ->and($auditLog->subject_type)->toBe($this->transaction->getMorphClass())
        ->and($auditLog->actor_id)->toBe($this->user->id)
        ->and($auditLog->metadata)->toBe(['reversal_transaction_id' => $reversal->id]);
});
