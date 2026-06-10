<?php

namespace App\Enums;

enum TransactionStatus: string
{
    case Draft = 'draft';
    case Posted = 'posted';
}
