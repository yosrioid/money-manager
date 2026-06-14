<?php

namespace App\Domain\Ledger;

use App\Models\Workspace;

class ConvertToBaseCurrency
{
    /**
     * Convert a set of minor-unit amounts grouped by currency code into the
     * workspace's default currency, using the workspace's configured
     * exchange rates.
     *
     * Currencies that are not the workspace's default currency and have no
     * configured rate are reported as unsupported and excluded from the
     * converted total.
     *
     * @param  array<string, int>  $amounts
     * @return array{total: int, unsupported_currencies: array<int, string>}
     */
    public function convert(Workspace $workspace, array $amounts): array
    {
        $rates = $workspace->exchangeRates()->pluck('rate_to_base', 'currency_code');

        $total = 0;
        $unsupportedCurrencies = [];

        foreach ($amounts as $currencyCode => $amount) {
            if ($currencyCode === $workspace->default_currency) {
                $total += $amount;

                continue;
            }

            $rate = $rates->get($currencyCode);

            if ($rate === null) {
                $unsupportedCurrencies[] = $currencyCode;

                continue;
            }

            $total += (int) round($amount * (float) $rate);
        }

        return ['total' => $total, 'unsupported_currencies' => $unsupportedCurrencies];
    }
}
