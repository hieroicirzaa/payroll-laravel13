<?php

namespace App\Domain\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case AdminCompany = 'admin_company';
    case Employee = 'employee';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::AdminCompany => 'Admin Company',
            self::Employee => 'Employee',
        };
    }
}
