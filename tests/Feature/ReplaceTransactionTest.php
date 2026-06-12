<?php

use App\Domain\Accounts\CreateAccountWithOpeningBalance;
use App\Domain\Ledger\CalculateAccountBalance;
use App\Domain\Ledger\ReplaceTransaction;
use App\Domain\Transactions\RecordIncomeExpense;
use App\Domain\Workspaces\CreatePersonalWorkspace;
use App\Enums\AccountType;
use App\Enums\AuditAction;
use App\Enums\LedgerEntryType;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\Category;
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

test('replacing an expense preserves a valid category reference', function () {
    $account = Account::factory()->for($this->workspace)->create();
    $category = Category::factory()->for($this->workspace)->expense()->create();
    $expense = app(RecordIncomeExpense::class)->record(
        $account,
        $category,
        TransactionType::Expense,
        2500,
        'Original expense',
        now(),
        $this->user,
    );

    $replacement = app(ReplaceTransaction::class)->replace($expense, [
        [
            'account_id' => $account->id,
            'type' => LedgerEntryType::Account,
            'amount' => -3000,
        ],
        [
            'account_id' => null,
            'category_id' => $category->id,
            'type' => LedgerEntryType::Category,
            'amount' => 3000,
        ],
    ], $this->user);

    expect($replacement->entries()->where('category_id', $category->id)->sole()->amount)->toBe(3000)
        ->and(app(CalculateAccountBalance::class)->calculate($account->fresh()))->toBe(-3000);
});

test('replacement rejects financial references from another workspace', function () {
    $outsider = User::factory()->create();
    $otherWorkspace = app(CreatePersonalWorkspace::class)->create($outsider);
    $otherAccount = Account::factory()->for($otherWorkspace)->create();
    $otherCategory = Category::factory()->for($otherWorkspace)->expense()->create();

    expect(fn () => app(ReplaceTransaction::class)->replace($this->transaction, [
        [
            'account_id' => $otherAccount->id,
            'type' => LedgerEntryType::Account,
            'amount' => 5000,
        ],
        [
            'account_id' => null,
            'category_id' => $otherCategory->id,
            'type' => LedgerEntryType::Category,
            'amount' => -5000,
        ],
    ], $this->user))->toThrow(LogicException::class);

    expect($this->transaction->fresh()->status)->toBe(TransactionStatus::Posted);
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

test('replacement requires at least two entries and preserves the original balance', function () {
    expect(fn () => app(ReplaceTransaction::class)->replace(
        $this->transaction,
        [],
        $this->user,
    ))->toThrow(LogicException::class, 'Replacement transactions require at least two entries.');

    expect($this->transaction->fresh()->status)->toBe(TransactionStatus::Posted)
        ->and($this->workspace->transactions()->count())->toBe(1)
        ->and(app(CalculateAccountBalance::class)->calculate($this->account->fresh()))->toBe(5000);
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
