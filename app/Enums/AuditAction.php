<?php

namespace App\Enums;

enum AuditAction: string
{
    case TransactionPosted = 'transaction.posted';
    case TransactionVoided = 'transaction.voided';
    case TransactionReversed = 'transaction.reversed';
    case TransactionReplaced = 'transaction.replaced';
}
