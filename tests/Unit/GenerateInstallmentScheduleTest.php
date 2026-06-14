<?php

use App\Domain\Accounts\GenerateInstallmentSchedule;
use Carbon\CarbonImmutable;

test('schedule splits the total amount evenly with no remainder', function () {
    $schedule = (new GenerateInstallmentSchedule)->generate(120000, 3, CarbonImmutable::parse('2026-07-01'));

    expect($schedule)->toHaveCount(3)
        ->and(array_column($schedule, 'amount'))->toBe([40000, 40000, 40000])
        ->and(array_column($schedule, 'sequence'))->toBe([1, 2, 3])
        ->and(array_map(fn ($installment) => $installment['due_date']->toDateString(), $schedule))
        ->toBe(['2026-07-01', '2026-08-01', '2026-09-01']);
});

test('schedule distributes the remainder across the earliest installments', function () {
    $schedule = (new GenerateInstallmentSchedule)->generate(100000, 3, CarbonImmutable::parse('2026-07-01'));

    expect(array_column($schedule, 'amount'))->toBe([33334, 33333, 33333])
        ->and(array_sum(array_column($schedule, 'amount')))->toBe(100000);
});

test('schedule uses monthly cadence without overflowing month-end dates', function () {
    $schedule = (new GenerateInstallmentSchedule)->generate(60000, 3, CarbonImmutable::parse('2026-01-31'));

    expect(array_map(fn ($installment) => $installment['due_date']->toDateString(), $schedule))
        ->toBe(['2026-01-31', '2026-02-28', '2026-03-31']);
});
