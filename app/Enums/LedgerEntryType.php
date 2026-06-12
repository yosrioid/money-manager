<?php

namespace App\Enums;

enum LedgerEntryType: string
{
    case Account = 'account';
    case OpeningBalanceEquity = 'opening_balance_equity';
    case Category = 'category';
}
