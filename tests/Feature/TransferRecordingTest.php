<?php

use App\Domain\Ledger\CalculateAccountBalance;
use App\Domain\Transactions\RecordTransfer;
use App\Domain\Workspaces\CreatePersonalWorkspace;
use App\Enums\AccountType;
use App\Enums\AuditAction;
use App\Enums\LedgerEntryType;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\CurrencySeeder;

function createTransferWorkspace(): array
{
    $user = User::factory()->create();
    $workspace = app(CreatePersonalWorkspace::class)->create($user);

    return [$user, $workspace];
}

function transferPayload(Account $sourceAccount, Account $destinationAccount, array $overrides = []): array
{
    return array_merge([
        'type' => 'transfer',
        'account_id' => $sourceAccount->id,
        'destination_account_id' => $destinationAccount->id,
        'amount' => 50000,
        'description' => 'Account transfer',
        'occurred_at' => now()->toDateTimeString(),
    ], $overrides);
}

test('user can transfer money between accounts', function () {
    [$user, $workspace] = createTransferWorkspace();
    $sourceAccount = Account::factory()->for($workspace)->create();
    $destinationAccount = Account::factory()->for($workspace)->create();

    $this->actingAs($user)
        ->post(route('transactions.store'), transferPayload($sourceAccount, $destinationAccount))
        ->assertRedirect(route('accounts.index'));

    $transaction = Transaction::query()->sole();

    expect($transaction)
        ->type->toBe(TransactionType::Transfer)
        ->status->toBe(TransactionStatus::Posted)
        ->and($transaction->entries()->sum('amount'))->toBe(0)
        ->and($transaction->entries()->where('type', LedgerEntryType::Category)->count())->toBe(0)
        ->and(app(CalculateAccountBalance::class)->calculate($sourceAccount->fresh()))->toBe(-50000)
        ->and(app(CalculateAccountBalance::class)->calculate($destinationAccount->fresh()))->toBe(50000);

    $auditLog = AuditLog::query()->sole();

    expect($auditLog)
        ->action->toBe(AuditAction::TransactionPosted)
        ->subject_id->toBe($transaction->id);
});

test('user can record a transfer fee as a linked expense leg', function () {
    [$user, $workspace] = createTransferWorkspace();
    $sourceAccount = Account::factory()->for($workspace)->create();
    $destinationAccount = Account::factory()->for($workspace)->create();
    $feeCategory = Category::factory()->for($workspace)->expense()->create();

    $this->actingAs($user)
        ->post(route('transactions.store'), transferPayload($sourceAccount, $destinationAccount, [
            'fee_amount' => 2500,
            'fee_category_id' => $feeCategory->id,
        ]))
        ->assertRedirect(route('accounts.index'));

    $transaction = Transaction::query()->sole();

    expect($transaction->entries()->sum('amount'))->toBe(0)
        ->and($transaction->entries()->count())->toBe(3)
        ->and($transaction->entries()->where('account_id', $sourceAccount->id)->sole()->amount)->toBe(-52500)
        ->and($transaction->entries()->where('account_id', $destinationAccount->id)->sole()->amount)->toBe(50000)
        ->and($transaction->entries()->where('category_id', $feeCategory->id)->sole()->amount)->toBe(2500);
});

test('cash withdrawal is recorded as a normal transfer', function () {
    [$user, $workspace] = createTransferWorkspace();
    $bankAccount = Account::factory()->for($workspace)->create(['type' => AccountType::BankAccount]);
    $cashAccount = Account::factory()->for($workspace)->create(['type' => AccountType::Cash]);

    $this->actingAs($user)
        ->post(route('transactions.store'), transferPayload($bankAccount, $cashAccount, [
            'description' => 'ATM withdrawal',
        ]))
        ->assertRedirect(route('accounts.index'));

    expect(Transaction::query()->sole()->type)->toBe(TransactionType::Transfer)
        ->and(app(CalculateAccountBalance::class)->calculate($bankAccount->fresh()))->toBe(-50000)
        ->and(app(CalculateAccountBalance::class)->calculate($cashAccount->fresh()))->toBe(50000);
});

test('credit card settlement is recorded as a transfer without an expense leg', function () {
    [$user, $workspace] = createTransferWorkspace();
    $bankAccount = Account::factory()->for($workspace)->create(['type' => AccountType::BankAccount]);
    $creditCardAccount = Account::factory()->for($workspace)->create(['type' => AccountType::CreditCard]);

    $this->actingAs($user)
        ->post(route('transactions.store'), transferPayload($bankAccount, $creditCardAccount, [
            'description' => 'Credit card settlement',
        ]))
        ->assertRedirect(route('accounts.index'));

    $transaction = Transaction::query()->sole();

    expect($transaction->type)->toBe(TransactionType::Transfer)
        ->and($transaction->entries()->where('type', LedgerEntryType::Category)->count())->toBe(0)
        ->and($transaction->entries()->sum('amount'))->toBe(0);
});

test('transfer rejects the same source and destination account', function () {
    [$user, $workspace] = createTransferWorkspace();
    $account = Account::factory()->for($workspace)->create();

    $this->actingAs($user)
        ->post(route('transactions.store'), transferPayload($account, $account))
        ->assertSessionHasErrors('destination_account_id');

    expect(Transaction::query()->count())->toBe(0);
});

test('transfer rejects accounts from another workspace', function () {
    [$user, $workspace] = createTransferWorkspace();
    [, $otherWorkspace] = createTransferWorkspace();
    $sourceAccount = Account::factory()->for($workspace)->create();
    $otherAccount = Account::factory()->for($otherWorkspace)->create();

    $this->actingAs($user)
        ->post(route('transactions.store'), transferPayload($sourceAccount, $otherAccount))
        ->assertSessionHasErrors('destination_account_id');

    expect(Transaction::query()->count())->toBe(0);
});

test('transfer rejects accounts with different currencies', function () {
    $this->seed(CurrencySeeder::class);

    [$user, $workspace] = createTransferWorkspace();
    $sourceAccount = Account::factory()->for($workspace)->create(['currency_code' => 'IDR']);
    $destinationAccount = Account::factory()->for($workspace)->create(['currency_code' => 'USD']);

    $this->actingAs($user)
        ->post(route('transactions.store'), transferPayload($sourceAccount, $destinationAccount))
        ->assertSessionHasErrors('destination_account_id');

    expect(Transaction::query()->count())->toBe(0);
});

test('transfer fee requires an expense category', function () {
    [$user, $workspace] = createTransferWorkspace();
    $sourceAccount = Account::factory()->for($workspace)->create();
    $destinationAccount = Account::factory()->for($workspace)->create();
    $incomeCategory = Category::factory()->for($workspace)->income()->create();

    $this->actingAs($user)
        ->post(route('transactions.store'), transferPayload($sourceAccount, $destinationAccount, [
            'fee_amount' => 2500,
        ]))
        ->assertSessionHasErrors('fee_category_id');

    $this->actingAs($user)
        ->post(route('transactions.store'), transferPayload($sourceAccount, $destinationAccount, [
            'fee_amount' => 2500,
            'fee_category_id' => $incomeCategory->id,
        ]))
        ->assertSessionHasErrors('fee_category_id');

    expect(Transaction::query()->count())->toBe(0);
});

test('transfer domain action rejects invalid amounts', function () {
    [$user, $workspace] = createTransferWorkspace();
    $sourceAccount = Account::factory()->for($workspace)->create();
    $destinationAccount = Account::factory()->for($workspace)->create();

    expect(fn () => app(RecordTransfer::class)->record(
        $sourceAccount,
        $destinationAccount,
        0,
        0,
        null,
        'Invalid transfer',
        now(),
        $user,
    ))->toThrow(LogicException::class);

    expect(Transaction::query()->count())->toBe(0);
});
