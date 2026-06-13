<?php

use App\Domain\Ledger\ReplaceTransaction;
use App\Domain\Ledger\ReverseTransaction;
use App\Domain\Transactions\RecordIncomeExpense;
use App\Domain\Transactions\SummarizeTransactionPeriod;
use App\Domain\Workspaces\CreatePersonalWorkspace;
use App\Enums\LedgerEntryType;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\User;
use Carbon\Carbon;

function setUpSummarizeTransactionPeriodWorkspace(): array
{
    $user = User::factory()->create();
    $workspace = app(CreatePersonalWorkspace::class)->create($user);
    $account = Account::factory()->for($workspace)->create();
    $incomeCategory = Category::factory()->for($workspace)->income()->create();
    $expenseCategory = Category::factory()->for($workspace)->expense()->create();

    return [$user, $workspace, $account, $incomeCategory, $expenseCategory];
}

test('forRange nets a same-period reversal to zero without double counting', function () {
    [$user, $workspace, $account, , $expenseCategory] = setUpSummarizeTransactionPeriodWorkspace();

    Carbon::setTestNow(now()->setDate(2026, 6, 10)->setTime(12, 0));

    $occurredAt = now();

    $expense = app(RecordIncomeExpense::class)->record(
        $account,
        $expenseCategory,
        TransactionType::Expense,
        15000,
        'Groceries',
        $occurredAt,
        $user,
    );

    app(ReverseTransaction::class)->reverse($expense, $user);

    $days = app(SummarizeTransactionPeriod::class)->forRange(
        $workspace,
        $occurredAt->copy()->startOfDay(),
        $occurredAt->copy()->startOfDay()->addDay(),
    );

    $date = $occurredAt->copy()->timezone($workspace->timezone)->toDateString();
    $currency = $account->currency_code;

    Carbon::setTestNow();

    expect($days[$date]['count'])->toBe(2)
        ->and($days[$date]['income'][$currency])->toBe(15000)
        ->and($days[$date]['expense'][$currency])->toBe(15000)
        ->and($days[$date]['net'][$currency])->toBe(0);
});

test('forRange excludes a replaced transaction and counts only its replacement', function () {
    [$user, $workspace, $account, , $expenseCategory] = setUpSummarizeTransactionPeriodWorkspace();

    Carbon::setTestNow(now()->setDate(2026, 6, 10)->setTime(12, 0));

    $occurredAt = now();

    $expense = app(RecordIncomeExpense::class)->record(
        $account,
        $expenseCategory,
        TransactionType::Expense,
        15000,
        'Groceries',
        $occurredAt,
        $user,
    );

    $replacementCategory = Category::factory()->for($workspace)->expense()->create();

    app(ReplaceTransaction::class)->replace($expense, [
        ['account_id' => $account->id, 'category_id' => null, 'type' => LedgerEntryType::Account, 'amount' => -15000],
        ['account_id' => null, 'category_id' => $replacementCategory->id, 'type' => LedgerEntryType::Category, 'amount' => 15000],
    ], $user);

    $days = app(SummarizeTransactionPeriod::class)->forRange(
        $workspace,
        $occurredAt->copy()->startOfDay(),
        $occurredAt->copy()->startOfDay()->addDay(),
    );

    $date = $occurredAt->copy()->timezone($workspace->timezone)->toDateString();
    $currency = $account->currency_code;

    Carbon::setTestNow();

    expect($days[$date]['count'])->toBe(1)
        ->and($days[$date]['income'])->toBe([])
        ->and($days[$date]['expense'][$currency])->toBe(15000)
        ->and($days[$date]['net'][$currency])->toBe(-15000);
});

test('weekStart honors the first_day_of_week preference', function () {
    [, $workspace] = setUpSummarizeTransactionPeriodWorkspace();

    $friday = Carbon::parse('2026-06-05', $workspace->timezone);

    $workspace->update(['first_day_of_week' => 1]);
    expect(app(SummarizeTransactionPeriod::class)->weekStart($workspace, $friday)->toDateString())
        ->toBe('2026-06-01');

    $workspace->update(['first_day_of_week' => 0]);
    expect(app(SummarizeTransactionPeriod::class)->weekStart($workspace, $friday)->toDateString())
        ->toBe('2026-05-31');
});

test('billingMonthStart honors a custom month start day', function () {
    [, $workspace] = setUpSummarizeTransactionPeriodWorkspace();

    $workspace->update(['month_start_day' => 25, 'adjust_month_for_weekend' => false]);

    $summarizeTransactionPeriod = app(SummarizeTransactionPeriod::class);

    // 2026-06-10 belongs to the billing month that started 2026-05-25.
    expect($summarizeTransactionPeriod->billingMonthStart($workspace, Carbon::parse('2026-06-10', $workspace->timezone))->toDateString())
        ->toBe('2026-05-25');

    // 2026-06-25 starts a new billing month.
    expect($summarizeTransactionPeriod->billingMonthStart($workspace, Carbon::parse('2026-06-25', $workspace->timezone))->toDateString())
        ->toBe('2026-06-25');
});

test('billingMonthStart shifts a weekend start day to the preceding Friday when adjustment is enabled', function () {
    [, $workspace] = setUpSummarizeTransactionPeriodWorkspace();

    // 2026-08-25 is a Tuesday; choose a start day that falls on a Saturday in
    // August 2026 (the 1st) to exercise the weekend adjustment.
    $workspace->update(['month_start_day' => 1, 'adjust_month_for_weekend' => true]);

    $summarizeTransactionPeriod = app(SummarizeTransactionPeriod::class);

    // 2026-08-01 is a Saturday, so the August billing month starts on the
    // preceding Friday, 2026-07-31, and dates through the end of August
    // belong to that same billing month.
    expect($summarizeTransactionPeriod->billingMonthStart($workspace, Carbon::parse('2026-08-01', $workspace->timezone))->toDateString())
        ->toBe('2026-07-31');

    expect($summarizeTransactionPeriod->billingMonthStart($workspace, Carbon::parse('2026-08-25', $workspace->timezone))->toDateString())
        ->toBe('2026-07-31');

    // 2026-07-30 still belongs to July's billing month, which starts on
    // 2026-07-01 (July 1st is a Wednesday, so no adjustment applies).
    expect($summarizeTransactionPeriod->billingMonthStart($workspace, Carbon::parse('2026-07-30', $workspace->timezone))->toDateString())
        ->toBe('2026-07-01');
});
