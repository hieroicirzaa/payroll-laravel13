<?php

namespace App\Application\Payroll;

use App\Domain\Enums\SalaryComponentType;
use App\Models\Employee;

class PayrollCalculator
{
    public function calculate(Employee $employee): array
    {
        $employee->loadMissing('salaryComponents.component');

        $items = [];
        $gross = (float) $employee->basic_salary;
        $deductions = 0.0;

        $items[] = [
            'salary_component_id' => null,
            'name' => 'Gaji Pokok',
            'type' => SalaryComponentType::Earning->value,
            'amount' => (float) $employee->basic_salary,
        ];

        foreach ($employee->salaryComponents as $employeeComponent) {
            $component = $employeeComponent->component;

            if (! $component || ! $component->is_active) {
                continue;
            }

            $amount = (float) $employeeComponent->amount;

            if ($component->type === SalaryComponentType::Earning) {
                $gross += $amount;
            } else {
                $deductions += abs($amount);
            }

            $items[] = [
                'salary_component_id' => $component->id,
                'name' => $component->name,
                'type' => $component->type->value,
                'amount' => $amount,
            ];
        }

        // Placeholder sederhana untuk pajak. Untuk produksi, ganti dengan modul PPh 21 resmi.
        $tax = round(max($gross - $deductions, 0) * 0.05, 2);
        $net = max($gross - $deductions - $tax, 0);

        return [
            'gross_amount' => round($gross, 2),
            'deduction_amount' => round($deductions, 2),
            'tax_amount' => $tax,
            'net_amount' => round($net, 2),
            'items' => $items,
        ];
    }
}
