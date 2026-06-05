<?php

namespace App\Application\Payroll;

use App\Domain\Enums\PayrollStatus;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PayrollPeriod;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class GeneratePayrollAction
{
    public function __construct(private readonly PayrollCalculator $calculator)
    {
    }

    public function execute(PayrollPeriod $period, User $actor): array
    {
        return DB::transaction(function () use ($period, $actor) {
            $employees = Employee::query()
                ->where('company_id', $period->company_id)
                ->where('status', 'active')
                ->with('salaryComponents.component')
                ->get();

            $created = 0;
            $updated = 0;

            foreach ($employees as $employee) {
                $result = $this->calculator->calculate($employee);

                $payroll = Payroll::query()->updateOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'payroll_period_id' => $period->id,
                    ],
                    [
                        'company_id' => $period->company_id,
                        'gross_amount' => $result['gross_amount'],
                        'deduction_amount' => $result['deduction_amount'],
                        'tax_amount' => $result['tax_amount'],
                        'net_amount' => $result['net_amount'],
                        'status' => PayrollStatus::Draft,
                        'failure_reason' => null,
                        'processed_by' => $actor->id,
                    ]
                );

                $payroll->wasRecentlyCreated ? $created++ : $updated++;

                $payroll->items()->delete();
                $payroll->items()->createMany($result['items']);
            }

            $period->update([
                'status' => 'generated',
                'generated_by' => $actor->id,
                'generated_at' => now(),
            ]);

            return compact('created', 'updated');
        });
    }
}
