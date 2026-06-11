<?php

namespace App\Enums;

enum TransactionStatus: string
{
    case Draft = 'draft';
    case Posted = 'posted';
    case Voided = 'voided';
    case Reversed = 'reversed';
    case Replaced = 'replaced';
}
