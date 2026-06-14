<?php

namespace App\Domain\Ledger;

use App\Enums\AccountType;
use App\Models\Workspace;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class CalculateNetAsset
{
    /**
     * Account types whose balances represent liabilities (debt owed) rather
     * than assets.
     *
     * @var array<int, AccountType>
     */
    private const LIABILITY_TYPES = [AccountType::Loan, AccountType::CreditCard];

    public function __construct(
        private readonly CalculateAccountBalance $calculateAccountBalance,
    ) {}

    /**
     * Sum the current balance of each active account with
     * `include_in_total` enabled, grouped by currency.
     *
     * @return array<string, int>
     */
    public function current(Workspace $workspace): array
    {
        $totals = [];

        $accounts = $workspace->accounts()
            ->active()
            ->where('include_in_total', true)
            ->get();

        foreach ($accounts as $account) {
            $totals[$account->currency_code] ??= 0;
            $totals[$account->currency_code] += $this->calculateAccountBalance->calculate($account);
        }

        return $totals;
    }

    /**
     * Classify the current balance of each active account with
     * `include_in_total` enabled as an asset or a liability, grouped by
     * currency, and compute the resulting net worth per currency.
     *
     * @return array{assets: array<string, int>, liabilities: array<string, int>, net: array<string, int>}
     */
    public function summary(Workspace $workspace): array
    {
        $assets = [];
        $liabilities = [];

        $accounts = $workspace->accounts()
            ->active()
            ->where('include_in_total', true)
            ->get();

        $liabilityTypes = array_map(fn (AccountType $type): string => $type->value, self::LIABILITY_TYPES);

        foreach ($accounts as $account) {
            $balance = $this->calculateAccountBalance->calculate($account);

            if (in_array($account->getRawOriginal('type'), $liabilityTypes, true)) {
                $liabilities[$account->currency_code] ??= 0;
                $liabilities[$account->currency_code] += $balance;
            } else {
                $assets[$account->currency_code] ??= 0;
                $assets[$account->currency_code] += $balance;
            }
        }

        $net = [];

        foreach (array_unique([...array_keys($assets), ...array_keys($liabilities)]) as $currency) {
            $net[$currency] = ($assets[$currency] ?? 0) + ($liabilities[$currency] ?? 0);
        }

        return ['assets' => $assets, 'liabilities' => $liabilities, 'net' => $net];
    }

    /**
     * Chart net worth (the sum of active, `include_in_total` account
     * balances, grouped by currency) as of the end of each of the last
     * `$months` workspace-local calendar months, ending with the month
     * containing the given reference date.
     *
     * @return array<int, array{month: string, net: array<string, int>}>
     */
    public function trend(Workspace $workspace, CarbonInterface $reference, int $months = 6): array
    {
        $accounts = $workspace->accounts()
            ->active()
            ->where('include_in_total', true)
            ->get();

        $referenceMonthEnd = Carbon::parse($reference->toDateString(), $workspace->timezone)->endOfMonth()->endOfDay();

        $points = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $asOf = $referenceMonthEnd->copy()->subMonthsNoOverflow($i);

            $totals = [];

            foreach ($accounts as $account) {
                $totals[$account->currency_code] = ($totals[$account->currency_code] ?? 0) + $this->calculateAccountBalance->calculateAsOf($account, $asOf->copy()->utc());
            }

            $points[] = ['month' => $asOf->format('Y-m'), 'net' => $totals];
        }

        return $points;
    }
}
