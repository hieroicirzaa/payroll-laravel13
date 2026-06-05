<?php

namespace App\Domain\Enums;

enum SalaryComponentType: string
{
    case Earning = 'earning';
    case Deduction = 'deduction';
}
