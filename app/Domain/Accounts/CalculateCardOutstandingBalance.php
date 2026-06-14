<?php

namespace App\Domain\Accounts;

use App\Domain\Ledger\CalculateAccountBalance;
use App\Models\Account;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class CalculateCardOutstandingBalance
{
    public function __construct(
        private readonly CalculateCardBillingCycle $calculateCardBillingCycle,
        private readonly CalculateAccountBalance $calculateAccountBalance,
    ) {}

    /**
     * Calculate a credit-card account's current outstanding balance and the
     * outstanding balance as of its most recently closed statement.
     *
     * @return array{current: int, statement: int, statement_closing_date: CarbonImmutable, payment_due_date: CarbonImmutable}
     */
    public function calculate(Account $account, CarbonInterface $referenceDate): array
    {
        $currentPeriod = $this->calculateCardBillingCycle->currentStatementPeriod($account, $referenceDate);
        $previousClosingDate = $currentPeriod['start']->subDay();
        $previousStatement = $this->calculateCardBillingCycle->currentStatementPeriod($account, $previousClosingDate);

        $currentBalance = $this->calculateAccountBalance->calculate($account);
        $statementBalance = $this->calculateAccountBalance->calculateAsOf($account, $previousStatement['end']->endOfDay());

        return [
            'current' => $currentBalance < 0 ? -$currentBalance : 0,
            'statement' => $statementBalance < 0 ? -$statementBalance : 0,
            'statement_closing_date' => $previousStatement['end'],
            'payment_due_date' => $previousStatement['payment_due'],
        ];
    }
}
