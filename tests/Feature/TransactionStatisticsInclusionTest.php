<?php

use App\Domain\Ledger\CalculateAccountBalance;
use App\Domain\Transactions\RecordIncomeExpense;
use App\Domain\Workspaces\CreatePersonalWorkspace;
use App\Enums\AuditAction;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function setUpStatisticsInclusionWorkspace(): array
{
    $user = User::factory()->create();
    $workspace = app(CreatePersonalWorkspace::class)->create($user);
    $account = Account::factory()->for($workspace)->create();
    $category = Category::factory()->for($workspace)->expense()->create();
    $transaction = app(RecordIncomeExpense::class)->record(
        $account,
        $category,
        TransactionType::Expense,
        2500,
        'Excluded expense',
        now()->setDate(2026, 6, 5)->setTime(10, 0),
        $user,
    );

    return [$user, $workspace, $account, $transaction];
}

test('a posted transaction can be excluded from statistics without changing ledger truth', function () {
    [$user, $workspace, $account, $transaction] = setUpStatisticsInclusionWorkspace();

    $balanceBefore = app(CalculateAccountBalance::class)->calculate($account);
    $entryAmountsBefore = $transaction->entries()->orderBy('id')->pluck('amount')->all();

    $this->actingAs($user)
        ->patch(route('transactions.statistics-inclusion.update', $transaction), [
            'include_in_statistics' => false,
        ])
        ->assertRedirect(route('transactions.show', $transaction));

    expect($transaction->fresh()->include_in_statistics)->toBeFalse()
        ->and(app(CalculateAccountBalance::class)->calculate($account))->toBe($balanceBefore)
        ->and($transaction->entries()->orderBy('id')->pluck('amount')->all())->toBe($entryAmountsBefore);

    $auditLog = AuditLog::query()
        ->where('subject_type', Transaction::class)
        ->where('subject_id', $transaction->id)
        ->where('action', AuditAction::TransactionStatisticsInclusionUpdated)
        ->sole();

    expect($auditLog->workspace_id)->toBe($workspace->id)
        ->and($auditLog->actor_id)->toBe($user->id)
        ->and($auditLog->metadata)->toBe([
            'previous_include_in_statistics' => true,
            'include_in_statistics' => false,
        ]);
});

test('excluded transactions remain visible but do not affect period statistics', function () {
    [$user, $workspace, $account, $transaction] = setUpStatisticsInclusionWorkspace();
    $transaction->update(['include_in_statistics' => false]);

    $this->actingAs($user)
        ->get(route('transactions.calendar', ['month' => '2026-06']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('days', []));

    $this->actingAs($user)
        ->get(route('transactions.summary', ['month' => '2026-06']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('count', 0)
            ->where('totals', ['income' => [], 'expense' => [], 'net' => []])
            ->where('accountMovements.0.id', $account->id)
            ->where('accountMovements.0.change', -2500));

    $this->actingAs($user)
        ->get(route('transactions.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('transactions.data.0.id', $transaction->id)
            ->where('transactions.data.0.include_in_statistics', false));

    $this->actingAs($user)
        ->get(route('transactions.show', $transaction))
        ->assertInertia(fn (Assert $page) => $page
            ->where('transaction.id', $transaction->id)
            ->where('transaction.include_in_statistics', false));
});

test('statistics inclusion update validates the value and denies another workspace', function () {
    [$user, $workspace, $account, $transaction] = setUpStatisticsInclusionWorkspace();

    $this->actingAs($user)
        ->patch(route('transactions.statistics-inclusion.update', $transaction), [
            'include_in_statistics' => 'invalid',
        ])
        ->assertSessionHasErrors('include_in_statistics');

    $outsider = User::factory()->create();
    app(CreatePersonalWorkspace::class)->create($outsider);

    $this->actingAs($outsider)
        ->patch(route('transactions.statistics-inclusion.update', $transaction), [
            'include_in_statistics' => false,
        ])
        ->assertNotFound();

    expect($transaction->fresh()->include_in_statistics)->toBeTrue();
});

test('a draft transaction cannot change statistics inclusion', function () {
    [$user, $workspace] = setUpStatisticsInclusionWorkspace();

    $draft = Transaction::query()->create([
        'workspace_id' => $workspace->id,
        'created_by' => $user->id,
        'type' => TransactionType::Expense,
        'status' => TransactionStatus::Draft,
        'currency_code' => 'IDR',
        'description' => 'Draft expense',
        'occurred_at' => now(),
        'posted_at' => null,
    ]);

    $this->actingAs($user)
        ->patch(route('transactions.statistics-inclusion.update', $draft), [
            'include_in_statistics' => false,
        ])
        ->assertNotFound();

    $draft->update(['status' => TransactionStatus::Voided]);

    expect(fn () => $draft->fresh()->update(['include_in_statistics' => false]))
        ->toThrow(LogicException::class);
});
