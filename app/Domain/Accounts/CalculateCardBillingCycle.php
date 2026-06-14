<?php

namespace App\Domain\Accounts;

use App\Models\Account;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use InvalidArgumentException;

class CalculateCardBillingCycle
{
    /**
     * Determine the statement period containing the given reference date,
     * along with the date the resulting statement is due for payment.
     *
     * @return array{start: CarbonImmutable, end: CarbonImmutable, payment_due: CarbonImmutable}
     */
    public function currentStatementPeriod(Account $account, CarbonInterface $referenceDate): array
    {
        $closingDay = $account->statement_closing_day;
        $paymentDueDay = $account->payment_due_day;

        if ($closingDay === null || $paymentDueDay === null) {
            throw new InvalidArgumentException('Account does not have a configured billing cycle.');
        }

        $reference = CarbonImmutable::instance($referenceDate)->startOfDay();
        $closingThisMonth = $reference->setDay($closingDay);

        $end = $reference->day <= $closingDay
            ? $closingThisMonth
            : $closingThisMonth->addMonthNoOverflow();

        $start = $end->subMonthNoOverflow()->addDay();

        $paymentDue = $paymentDueDay <= $closingDay
            ? $end->addMonthNoOverflow()->setDay($paymentDueDay)
            : $end->setDay($paymentDueDay);

        return [
            'start' => $start,
            'end' => $end,
            'payment_due' => $paymentDue,
        ];
    }
}
