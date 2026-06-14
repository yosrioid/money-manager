<?php

use App\Domain\Accounts\CalculateCardBillingCycle;
use App\Models\Account;
use Carbon\CarbonImmutable;

function cardAccount(int $closingDay, int $paymentDueDay): Account
{
    return new Account([
        'statement_closing_day' => $closingDay,
        'payment_due_day' => $paymentDueDay,
    ]);
}

test('reference date before the closing day falls in the current closing period', function () {
    $account = cardAccount(closingDay: 25, paymentDueDay: 10);
    $period = (new CalculateCardBillingCycle)->currentStatementPeriod($account, CarbonImmutable::parse('2026-06-10'));

    expect($period['start']->toDateString())->toBe('2026-05-26')
        ->and($period['end']->toDateString())->toBe('2026-06-25')
        ->and($period['payment_due']->toDateString())->toBe('2026-07-10');
});

test('reference date after the closing day falls in the next closing period', function () {
    $account = cardAccount(closingDay: 25, paymentDueDay: 10);
    $period = (new CalculateCardBillingCycle)->currentStatementPeriod($account, CarbonImmutable::parse('2026-06-26'));

    expect($period['start']->toDateString())->toBe('2026-06-26')
        ->and($period['end']->toDateString())->toBe('2026-07-25')
        ->and($period['payment_due']->toDateString())->toBe('2026-08-10');
});

test('reference date on the closing day falls in the current closing period', function () {
    $account = cardAccount(closingDay: 25, paymentDueDay: 10);
    $period = (new CalculateCardBillingCycle)->currentStatementPeriod($account, CarbonImmutable::parse('2026-06-25'));

    expect($period['start']->toDateString())->toBe('2026-05-26')
        ->and($period['end']->toDateString())->toBe('2026-06-25')
        ->and($period['payment_due']->toDateString())->toBe('2026-07-10');
});

test('a payment due day after the closing day falls in the same statement month', function () {
    $account = cardAccount(closingDay: 5, paymentDueDay: 20);
    $period = (new CalculateCardBillingCycle)->currentStatementPeriod($account, CarbonImmutable::parse('2026-06-10'));

    expect($period['start']->toDateString())->toBe('2026-06-06')
        ->and($period['end']->toDateString())->toBe('2026-07-05')
        ->and($period['payment_due']->toDateString())->toBe('2026-07-20');
});

test('it throws when the account has no configured billing cycle', function () {
    $account = new Account;

    (new CalculateCardBillingCycle)->currentStatementPeriod($account, CarbonImmutable::now());
})->throws(InvalidArgumentException::class);
