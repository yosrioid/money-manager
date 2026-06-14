<?php

use App\Domain\Ledger\CalculateAccountBalance;
use App\Domain\Workspaces\CreatePersonalWorkspace;
use App\Enums\AccountType;
use App\Models\Account;
use App\Models\AccountGroup;
use App\Models\User;
use Database\Seeders\CurrencySeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(CurrencySeeder::class);
});

function createUserWithPersonalWorkspace(): array
{
    $user = User::factory()->create();
    $workspace = app(CreatePersonalWorkspace::class)->create($user);

    return [$user, $workspace];
}

test('accounts page only displays active resources from the current workspace', function () {
    [$user, $workspace] = createUserWithPersonalWorkspace();
    [, $otherWorkspace] = createUserWithPersonalWorkspace();

    $group = AccountGroup::factory()->for($workspace)->create();
    AccountGroup::factory()->for($workspace)->archived()->create();
    AccountGroup::factory()->for($otherWorkspace)->create();

    Account::factory()->for($workspace)->inGroup($group)->create();
    Account::factory()->for($workspace)->archived()->create();
    Account::factory()->for($otherWorkspace)->create();

    $this->actingAs($user)
        ->get(route('accounts.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('accounts/Index')
            ->has('accountGroups', 1)
            ->has('accounts', 1)
            ->where('accounts.0.balance', 0)
            ->where('accountGroups.0.id', $group->id),
        );
});

test('user can create an account group in the current workspace', function () {
    [$user, $workspace] = createUserWithPersonalWorkspace();

    $this->actingAs($user)
        ->post(route('account-groups.store'), ['name' => 'Daily accounts'])
        ->assertRedirect(route('accounts.index'));

    expect($workspace->accountGroups()->sole())
        ->name->toBe('Daily accounts')
        ->position->toBe(0);
});

test('user can create an account in a group from the current workspace', function () {
    [$user, $workspace] = createUserWithPersonalWorkspace();
    $group = AccountGroup::factory()->for($workspace)->create();

    $this->actingAs($user)
        ->post(route('accounts.store'), [
            'name' => 'Main bank',
            'type' => AccountType::BankAccount->value,
            'currency_code' => 'IDR',
            'opening_balance' => 100000,
            'account_group_id' => $group->id,
            'description' => 'Primary account',
            'is_visible' => true,
            'include_in_total' => true,
        ])
        ->assertRedirect(route('accounts.index'));

    expect($workspace->accounts()->sole())
        ->name->toBe('Main bank')
        ->account_group_id->toBe($group->id);

    expect(app(CalculateAccountBalance::class)->calculate($workspace->accounts()->sole()))
        ->toBe(100000);
});

test('creating a credit card account requires a credit limit', function () {
    [$user, $workspace] = createUserWithPersonalWorkspace();

    $this->actingAs($user)
        ->post(route('accounts.store'), [
            'name' => 'Visa',
            'type' => AccountType::CreditCard->value,
            'currency_code' => 'IDR',
            'opening_balance' => 0,
            'statement_closing_day' => 25,
            'payment_due_day' => 10,
        ])
        ->assertSessionHasErrors('credit_limit');

    $this->actingAs($user)
        ->post(route('accounts.store'), [
            'name' => 'Visa',
            'type' => AccountType::CreditCard->value,
            'currency_code' => 'IDR',
            'opening_balance' => 0,
            'credit_limit' => 5000000,
            'statement_closing_day' => 25,
            'payment_due_day' => 10,
        ])
        ->assertRedirect(route('accounts.index'));

    expect($workspace->accounts()->sole())
        ->name->toBe('Visa')
        ->credit_limit->toBe(5000000)
        ->statement_closing_day->toBe(25)
        ->payment_due_day->toBe(10);
});

test('creating a credit card account requires a billing cycle', function () {
    [$user] = createUserWithPersonalWorkspace();

    $this->actingAs($user)
        ->post(route('accounts.store'), [
            'name' => 'Visa',
            'type' => AccountType::CreditCard->value,
            'currency_code' => 'IDR',
            'opening_balance' => 0,
            'credit_limit' => 5000000,
        ])
        ->assertSessionHasErrors('statement_closing_day');
});

test('credit limit is rejected for non credit card accounts', function () {
    [$user] = createUserWithPersonalWorkspace();

    $this->actingAs($user)
        ->post(route('accounts.store'), [
            'name' => 'Main bank',
            'type' => AccountType::BankAccount->value,
            'currency_code' => 'IDR',
            'opening_balance' => 0,
            'credit_limit' => 5000000,
        ])
        ->assertSessionHasErrors('credit_limit');
});

test('billing cycle days are rejected for non credit card accounts', function () {
    [$user] = createUserWithPersonalWorkspace();

    $this->actingAs($user)
        ->post(route('accounts.store'), [
            'name' => 'Main bank',
            'type' => AccountType::BankAccount->value,
            'currency_code' => 'IDR',
            'opening_balance' => 0,
            'statement_closing_day' => 25,
            'payment_due_day' => 10,
        ])
        ->assertSessionHasErrors('statement_closing_day');
});

test('changing an account type away from credit card clears its credit limit and billing cycle', function () {
    [$user, $workspace] = createUserWithPersonalWorkspace();
    $account = Account::factory()->for($workspace)->creditCard(5000000)->create();

    $this->actingAs($user)
        ->patch(route('accounts.update', $account), [
            'name' => $account->name,
            'type' => AccountType::BankAccount->value,
            'currency_code' => $account->currency_code,
            'is_visible' => true,
            'include_in_total' => true,
        ])
        ->assertRedirect(route('accounts.index'));

    expect($account->fresh())
        ->type->toBe(AccountType::BankAccount)
        ->credit_limit->toBeNull()
        ->statement_closing_day->toBeNull()
        ->payment_due_day->toBeNull();
});

test('account cannot reference a group from another workspace', function () {
    [$user] = createUserWithPersonalWorkspace();
    [, $otherWorkspace] = createUserWithPersonalWorkspace();
    $otherGroup = AccountGroup::factory()->for($otherWorkspace)->create();

    $this->actingAs($user)
        ->post(route('accounts.store'), [
            'name' => 'Invalid account',
            'type' => AccountType::Cash->value,
            'currency_code' => 'IDR',
            'opening_balance' => 0,
            'account_group_id' => $otherGroup->id,
        ])
        ->assertSessionHasErrors('account_group_id');
});

test('cross workspace account and group changes are forbidden', function () {
    [$user] = createUserWithPersonalWorkspace();
    [, $otherWorkspace] = createUserWithPersonalWorkspace();
    $otherGroup = AccountGroup::factory()->for($otherWorkspace)->create();
    $otherAccount = Account::factory()->for($otherWorkspace)->create();

    $this->actingAs($user)
        ->patch(route('account-groups.update', $otherGroup), ['name' => 'Changed'])
        ->assertForbidden();

    $this->actingAs($user)
        ->delete(route('accounts.destroy', $otherAccount))
        ->assertForbidden();
});

test('accounts and account groups are archived without destructive deletion', function () {
    [$user, $workspace] = createUserWithPersonalWorkspace();
    $group = AccountGroup::factory()->for($workspace)->create();
    $account = Account::factory()->for($workspace)->inGroup($group)->create();

    $this->actingAs($user)
        ->delete(route('account-groups.destroy', $group))
        ->assertRedirect(route('accounts.index'));

    $this->actingAs($user)
        ->delete(route('accounts.destroy', $account))
        ->assertRedirect(route('accounts.index'));

    expect($group->fresh())
        ->not->toBeNull()
        ->archived_at->not->toBeNull()
        ->and($account->fresh())
        ->not->toBeNull()
        ->archived_at->not->toBeNull();
});
