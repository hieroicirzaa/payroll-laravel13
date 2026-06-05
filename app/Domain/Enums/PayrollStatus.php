<?php

namespace App\Domain\Enums;

enum PayrollStatus: string
{
    case Draft = 'draft';
    case Paid = 'paid';
    case Failed = 'failed';
}
