<?php

use App\Domain\Accounts\GenerateInstallmentSchedule;
use App\Domain\Accounts\SummarizeInstallmentPlan;
use App\Models\InstallmentPlan;
use Carbon\CarbonImmutable;

function makeInstallmentPlan(int $totalAmount, int $installmentCount, string $firstDueDate): InstallmentPlan
{
    return new InstallmentPlan([
        'total_amount' => $totalAmount,
        'installment_count' => $installmentCount,
        'first_due_date' => $firstDueDate,
    ]);
}

test('summary reports no paid installments before the first due date', function () {
    $plan = makeInstallmentPlan(120000, 3, '2026-07-01');
    $summary = (new SummarizeInstallmentPlan(new GenerateInstallmentSchedule))->summarize($plan, CarbonImmutable::parse('2026-06-15'));

    expect($summary['paid_count'])->toBe(0)
        ->and($summary['paid_amount'])->toBe(0)
        ->and($summary['due'])->toBe(['sequence' => 1, 'due_date' => '2026-07-01', 'amount' => 40000])
        ->and($summary['remaining_count'])->toBe(2)
        ->and($summary['remaining_amount'])->toBe(80000);
});

test('summary reports installments paid as their due dates pass', function () {
    $plan = makeInstallmentPlan(120000, 3, '2026-07-01');
    $summary = (new SummarizeInstallmentPlan(new GenerateInstallmentSchedule))->summarize($plan, CarbonImmutable::parse('2026-08-01'));

    expect($summary['paid_count'])->toBe(2)
        ->and($summary['paid_amount'])->toBe(80000)
        ->and($summary['due'])->toBe(['sequence' => 3, 'due_date' => '2026-09-01', 'amount' => 40000])
        ->and($summary['remaining_count'])->toBe(0)
        ->and($summary['remaining_amount'])->toBe(0);
});

test('summary reports a fully paid plan with no due installment', function () {
    $plan = makeInstallmentPlan(120000, 3, '2026-07-01');
    $summary = (new SummarizeInstallmentPlan(new GenerateInstallmentSchedule))->summarize($plan, CarbonImmutable::parse('2026-09-01'));

    expect($summary['paid_count'])->toBe(3)
        ->and($summary['paid_amount'])->toBe(120000)
        ->and($summary['due'])->toBeNull()
        ->and($summary['remaining_count'])->toBe(0)
        ->and($summary['remaining_amount'])->toBe(0);
});
