<?php

namespace App\Domain\Accounts;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class GenerateInstallmentSchedule
{
    /**
     * Generate a monthly installment schedule whose amounts sum exactly to
     * the total amount, distributing the remainder across the earliest
     * installments.
     *
     * @return array<int, array{sequence: int, due_date: CarbonImmutable, amount: int}>
     */
    public function generate(int $totalAmount, int $installmentCount, CarbonInterface $firstDueDate): array
    {
        $baseAmount = intdiv($totalAmount, $installmentCount);
        $remainder = $totalAmount % $installmentCount;
        $firstDueDate = CarbonImmutable::instance($firstDueDate)->startOfDay();

        $schedule = [];

        for ($sequence = 1; $sequence <= $installmentCount; $sequence++) {
            $schedule[] = [
                'sequence' => $sequence,
                'due_date' => $firstDueDate->addMonthsNoOverflow($sequence - 1),
                'amount' => $baseAmount + ($sequence <= $remainder ? 1 : 0),
            ];
        }

        return $schedule;
    }
}
