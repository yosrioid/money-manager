<?php

namespace App\Enums;

enum TransactionType: string
{
    case OpeningBalance = 'opening_balance';
    case Income = 'income';
    case Expense = 'expense';
    case Transfer = 'transfer';
}
