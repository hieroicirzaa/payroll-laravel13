<?php

namespace Database\Seeders;

use App\Domain\Enums\SalaryComponentType;
use App\Domain\Enums\UserRole;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeSalaryComponent;
use App\Models\PayrollPeriod;
use App\Models\SalaryComponent;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = User::updateOrCreate(
            ['email' => 'superadmin@payroll.local'],
            [
                'name' => 'Super Admin Payroll',
                'password' => 'Password123!',
                'role' => UserRole::SuperAdmin,
                'is_active' => true,
            ]
        );

        $alpha = Company::updateOrCreate(
            ['code' => 'ALPHA'],
            [
                'name' => 'PT Alpha Teknologi Indonesia',
                'email' => 'hr@alpha.local',
                'phone' => '021-1111-2222',
                'address' => 'Jakarta Selatan',
                'tax_number' => '01.234.567.8-999.000',
                'is_active' => true,
            ]
        );

        $beta = Company::updateOrCreate(
            ['code' => 'BETA'],
            [
                'name' => 'PT Beta Solusi Digital',
                'email' => 'hr@beta.local',
                'phone' => '022-3333-4444',
                'address' => 'Bandung',
                'tax_number' => '02.345.678.9-999.000',
                'is_active' => true,
            ]
        );

        $adminAlpha = User::updateOrCreate(
            ['email' => 'admin.alpha@payroll.local'],
            [
                'company_id' => $alpha->id,
                'name' => 'Admin Alpha',
                'password' => 'Password123!',
                'role' => UserRole::AdminCompany,
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'admin.beta@payroll.local'],
            [
                'company_id' => $beta->id,
                'name' => 'Admin Beta',
                'password' => 'Password123!',
                'role' => UserRole::AdminCompany,
                'is_active' => true,
            ]
        );

        $components = [
            ['name' => 'Tunjangan Transport', 'code' => 'TRANS', 'type' => SalaryComponentType::Earning, 'is_taxable' => true],
            ['name' => 'Tunjangan Makan', 'code' => 'MEAL', 'type' => SalaryComponentType::Earning, 'is_taxable' => true],
            ['name' => 'Bonus Kinerja', 'code' => 'BONUS', 'type' => SalaryComponentType::Earning, 'is_taxable' => true],
            ['name' => 'Potongan Keterlambatan', 'code' => 'LATE', 'type' => SalaryComponentType::Deduction, 'is_taxable' => false],
            ['name' => 'Potongan BPJS', 'code' => 'BPJS', 'type' => SalaryComponentType::Deduction, 'is_taxable' => false],
        ];

        foreach ($components as $component) {
            SalaryComponent::updateOrCreate(
                ['code' => $component['code']],
                [
                    'name' => $component['name'],
                    'type' => $component['type'],
                    'is_taxable' => $component['is_taxable'],
                    'is_active' => true,
                ]
            );
        }

        $employeeUser = User::updateOrCreate(
            ['email' => 'employee.alpha@payroll.local'],
            [
                'company_id' => $alpha->id,
                'name' => 'Ahmad Payroll Employee',
                'password' => 'Password123!',
                'role' => UserRole::Employee,
                'is_active' => true,
            ]
        );

        $employee = Employee::updateOrCreate(
            ['employee_number' => 'ALP-EMP-001'],
            [
                'company_id' => $alpha->id,
                'user_id' => $employeeUser->id,
                'position' => 'Software Engineer',
                'department' => 'Engineering',
                'join_date' => now()->subYear()->toDateString(),
                'employment_status' => 'permanent',
                'basic_salary' => 8500000,
                'nik' => '3276010101010001',
                'npwp' => '09.123.456.7-888.000',
                'bank_name' => 'BCA',
                'bank_account_number' => '1234567890',
                'bank_account_name' => 'Ahmad Payroll Employee',
                'address' => 'Jakarta',
                'phone' => '081234567890',
                'status' => 'active',
            ]
        );

        $transport = SalaryComponent::where('code', 'TRANS')->first();
        $meal = SalaryComponent::where('code', 'MEAL')->first();
        $bpjs = SalaryComponent::where('code', 'BPJS')->first();

        foreach ([
            [$transport?->id, 750000],
            [$meal?->id, 600000],
            [$bpjs?->id, 250000],
        ] as [$componentId, $amount]) {
            if ($componentId) {
                EmployeeSalaryComponent::updateOrCreate(
                    ['employee_id' => $employee->id, 'salary_component_id' => $componentId],
                    [
                        'company_id' => $alpha->id,
                        'amount' => $amount,
                        'is_recurring' => true,
                        'effective_from' => now()->startOfYear()->toDateString(),
                    ]
                );
            }
        }

        PayrollPeriod::updateOrCreate(
            ['company_id' => $alpha->id, 'period_month' => now()->month, 'period_year' => now()->year],
            [
                'name' => 'Payroll '.now()->translatedFormat('F Y'),
                'start_date' => now()->startOfMonth()->toDateString(),
                'end_date' => now()->endOfMonth()->toDateString(),
                'status' => 'open',
            ]
        );
    }
}
