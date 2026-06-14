<?php

namespace App\Enums;

enum AccountType: string
{
    case Cash = 'cash';
    case BankAccount = 'bank_account';
    case EWallet = 'ewallet';
    case Savings = 'savings';
    case Investment = 'investment';
    case Asset = 'asset';
    case Loan = 'loan';
    case CreditCard = 'credit_card';
    case DebitCard = 'debit_card';
}
