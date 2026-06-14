<?php

use App\Domain\Accounts\CreateAccountWithOpeningBalance;
use App\Domain\Ledger\CalculateAccountBalance;
use App\Domain\Workspaces\CreatePersonalWorkspace;
use App\Enums\AccountType;
use App\Enums\LedgerEntryType;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Workspace;
use Database\Seeders\CurrencySeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(CurrencySeeder::class);

    $this->user = User::factory()->create();
    $this->workspace = app(CreatePersonalWorkspace::class)->create($this->user);
    $this->account = app(CreateAccountWithOpeningBalance::class)->create($this->workspace, [
        'name' => 'Balance account',
        'type' => AccountType::Cash,
        'currency_code' => 'IDR',
        'opening_balance' => 0,
    ], $this->user);
});

function createDraftTransactionWithEntries(Workspace $workspace, Account $account, User $user, int $amount, ?DateTimeInterface $occurredAt = null): Transaction
{
    $transaction = Transaction::query()->create([
        'workspace_id' => $workspace->id,
        'created_by' => $user->id,
        'type' => TransactionType::OpeningBalance,
        'status' => TransactionStatus::Draft,
        'currency_code' => $account->currency_code,
        'description' => 'Manual ledger transaction',
        'occurred_at' => $occurredAt ?? now(),
        'posted_at' => null,
    ]);

    $transaction->entries()->createMany([
        [
            'workspace_id' => $workspace->id,
            'account_id' => $account->id,
            'type' => LedgerEntryType::Account,
            'currency_code' => $account->currency_code,
            'amount' => $amount,
        ],
        [
            'workspace_id' => $workspace->id,
            'account_id' => null,
            'type' => LedgerEntryType::OpeningBalanceEquity,
            'currency_code' => $account->currency_code,
            'amount' => -$amount,
        ],
    ]);

    return $transaction;
}

test('draft transactions do not affect the account balance', function () {
    $transaction = createDraftTransactionWithEntries($this->workspace, $this->account, $this->user, 5000);

    expect(app(CalculateAccountBalance::class)->calculate($this->account))->toBe(0);

    $transaction->update(['status' => TransactionStatus::Posted, 'posted_at' => now()]);

    expect(app(CalculateAccountBalance::class)->calculate($this->account->fresh()))->toBe(5000);
});

test('balance as of a date excludes transactions posted after that date', function () {
    $earlier = now()->subDays(2);
    $later = now();

    $first = createDraftTransactionWithEntries($this->workspace, $this->account, $this->user, 1000, $earlier);
    $first->update(['status' => TransactionStatus::Posted, 'posted_at' => $earlier]);

    $second = createDraftTransactionWithEntries($this->workspace, $this->account, $this->user, 2000, $later);
    $second->update(['status' => TransactionStatus::Posted, 'posted_at' => $later]);

    $calculator = app(CalculateAccountBalance::class);
    $account = $this->account->fresh();

    expect($calculator->calculateAsOf($account, $earlier))->toBe(1000)
        ->and($calculator->calculateAsOf($account, $later))->toBe(3000)
        ->and($calculator->calculate($account))->toBe(3000);
});

test('credit card accounts expose their ledger balance and credit limit', function () {
    $creditCard = Account::factory()->for($this->workspace)->creditCard(5000000)->create(['position' => 1]);
    $transaction = createDraftTransactionWithEntries($this->workspace, $creditCard, $this->user, -1500000);
    $transaction->update(['status' => TransactionStatus::Posted, 'posted_at' => now()]);

    $this->actingAs($this->user)
        ->get(route('accounts.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('accounts.1.balance', -1500000)
            ->where('accounts.1.credit_limit', 5000000),
        );
});

test('account group index reflects only posted ledger entries in account balances', function () {
    $transaction = createDraftTransactionWithEntries($this->workspace, $this->account, $this->user, 7500);

    $this->actingAs($this->user)
        ->get(route('accounts.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('accounts.0.balance', 0),
        );

    $transaction->update(['status' => TransactionStatus::Posted, 'posted_at' => now()]);

    $this->actingAs($this->user)
        ->get(route('accounts.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('accounts.0.balance', 7500),
        );
});
