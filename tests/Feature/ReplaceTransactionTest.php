<?php

use App\Domain\Accounts\CreateAccountWithOpeningBalance;
use App\Domain\Ledger\CalculateAccountBalance;
use App\Domain\Ledger\ReplaceTransaction;
use App\Domain\Workspaces\CreatePersonalWorkspace;
use App\Enums\AccountType;
use App\Enums\AuditAction;
use App\Enums\LedgerEntryType;
use App\Enums\TransactionStatus;
use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\CurrencySeeder;

beforeEach(function () {
    $this->seed(CurrencySeeder::class);

    $this->user = User::factory()->create();
    $this->workspace = app(CreatePersonalWorkspace::class)->create($this->user);

    $this->account = app(CreateAccountWithOpeningBalance::class)->create($this->workspace, [
        'name' => 'Replaceable account',
        'type' => AccountType::Cash,
        'currency_code' => 'IDR',
        'opening_balance' => 5000,
    ], $this->user);

    $this->transaction = $this->account->ledgerEntries()->sole()->transaction;
});

test('replacing a posted transaction creates a posted replacement and marks the original as replaced', function () {
    $replacement = app(ReplaceTransaction::class)->replace($this->transaction, [
        [
            'account_id' => $this->account->id,
            'type' => LedgerEntryType::Account,
            'amount' => 7500,
        ],
        [
            'account_id' => null,
            'type' => LedgerEntryType::OpeningBalanceEquity,
            'amount' => -7500,
        ],
    ], $this->user, 'Corrected opening balance');

    expect($replacement->status)->toBe(TransactionStatus::Posted)
        ->and($replacement->replaces_transaction_id)->toBe($this->transaction->id)
        ->and($replacement->description)->toBe('Corrected opening balance')
        ->and($this->transaction->fresh()->status)->toBe(TransactionStatus::Replaced)
        ->and(app(CalculateAccountBalance::class)->calculate($this->account->fresh()))->toBe(7500);
});

test('replacement entries that do not balance to zero throw and create nothing', function () {
    expect(fn () => app(ReplaceTransaction::class)->replace($this->transaction, [
        [
            'account_id' => $this->account->id,
            'type' => LedgerEntryType::Account,
            'amount' => 7500,
        ],
        [
            'account_id' => null,
            'type' => LedgerEntryType::OpeningBalanceEquity,
            'amount' => -7000,
        ],
    ], $this->user))->toThrow(LogicException::class);

    expect($this->transaction->fresh()->status)->toBe(TransactionStatus::Posted)
        ->and($this->workspace->transactions()->count())->toBe(1);
});

test('replacing a non posted transaction throws', function () {
    app(ReplaceTransaction::class)->replace($this->transaction, [
        [
            'account_id' => $this->account->id,
            'type' => LedgerEntryType::Account,
            'amount' => 1000,
        ],
        [
            'account_id' => null,
            'type' => LedgerEntryType::OpeningBalanceEquity,
            'amount' => -1000,
        ],
    ], $this->user);

    expect(fn () => app(ReplaceTransaction::class)->replace($this->transaction->fresh(), [
        [
            'account_id' => $this->account->id,
            'type' => LedgerEntryType::Account,
            'amount' => 2000,
        ],
        [
            'account_id' => null,
            'type' => LedgerEntryType::OpeningBalanceEquity,
            'amount' => -2000,
        ],
    ], $this->user))->toThrow(LogicException::class);
});

test('replacing a transaction records an audit log entry', function () {
    $replacement = app(ReplaceTransaction::class)->replace($this->transaction, [
        [
            'account_id' => $this->account->id,
            'type' => LedgerEntryType::Account,
            'amount' => 1000,
        ],
        [
            'account_id' => null,
            'type' => LedgerEntryType::OpeningBalanceEquity,
            'amount' => -1000,
        ],
    ], $this->user);

    $auditLog = AuditLog::query()->where('subject_id', $this->transaction->id)->sole();

    expect($auditLog->action)->toBe(AuditAction::TransactionReplaced)
        ->and($auditLog->subject_type)->toBe($this->transaction->getMorphClass())
        ->and($auditLog->actor_id)->toBe($this->user->id)
        ->and($auditLog->metadata)->toBe(['replacement_transaction_id' => $replacement->id]);
});
