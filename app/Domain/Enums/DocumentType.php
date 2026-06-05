<?php

namespace App\Domain\Enums;

enum DocumentType: string
{
    case Ktp = 'ktp';
    case Ijazah = 'ijazah';
    case Npwp = 'npwp';
    case Contract = 'contract';
    case Other = 'other';
}
