<?php

namespace App\Domain\Accounts;

use App\Models\InstallmentPlan;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class SummarizeInstallmentPlan
{
    public function __construct(
        private readonly GenerateInstallmentSchedule $generateInstallmentSchedule,
    ) {}

    /**
     * Summarize an installment plan's schedule into paid, due, and remaining
     * installments as of the given reference date.
     *
     * @return array{total_amount: int, installment_count: int, paid_count: int, paid_amount: int, due: ?array{sequence: int, due_date: string, amount: int}, remaining_count: int, remaining_amount: int}
     */
    public function summarize(InstallmentPlan $plan, CarbonInterface $referenceDate): array
    {
        $schedule = $this->generateInstallmentSchedule->generate(
            $plan->total_amount,
            $plan->installment_count,
            CarbonImmutable::parse($plan->getAttributes()['first_due_date']),
        );

        $referenceDate = $referenceDate->startOfDay();

        $paid = array_values(array_filter($schedule, fn (array $installment): bool => $installment['due_date']->lte($referenceDate)));
        $upcoming = array_values(array_filter($schedule, fn (array $installment): bool => $installment['due_date']->gt($referenceDate)));

        $due = $upcoming[0] ?? null;
        $remaining = array_slice($upcoming, 1);

        return [
            'total_amount' => $plan->total_amount,
            'installment_count' => $plan->installment_count,
            'paid_count' => count($paid),
            'paid_amount' => array_sum(array_column($paid, 'amount')),
            'due' => $due === null ? null : [
                'sequence' => $due['sequence'],
                'due_date' => $due['due_date']->toDateString(),
                'amount' => $due['amount'],
            ],
            'remaining_count' => count($remaining),
            'remaining_amount' => array_sum(array_column($remaining, 'amount')),
        ];
    }
}
