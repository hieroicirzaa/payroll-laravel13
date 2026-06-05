<?php

namespace App\Application\Employees;

use App\Models\Employee;
use App\Models\User;

class CompanyScope
{
    public function assertEmployeeAccessible(User $user, Employee $employee): void
    {
        if ($user->isSuperAdmin()) {
            return;
        }

        if ($user->isAdminCompany() && $user->company_id === $employee->company_id) {
            return;
        }

        if ($user->isEmployee() && $user->employee?->id === $employee->id) {
            return;
        }

        abort(403, 'Data karyawan tidak berada dalam scope akses Anda.');
    }

    public function targetCompanyId(User $user, ?int $requestedCompanyId = null): int
    {
        if ($user->isSuperAdmin()) {
            if (! $requestedCompanyId) {
                abort(422, 'company_id wajib diisi untuk super admin.');
            }

            return $requestedCompanyId;
        }

        if (! $user->company_id) {
            abort(403, 'Akun tidak memiliki company_id.');
        }

        return $user->company_id;
    }
}
