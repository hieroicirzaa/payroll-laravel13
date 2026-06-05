<?php

namespace App\Http\Controllers\Web;

use App\Domain\Enums\PayrollStatus;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PayrollPeriod;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $user = request()->user();

        $companies = Company::query();
        $users = User::query();
        $employees = Employee::query();
        $payrolls = Payroll::query();
        $periods = PayrollPeriod::query();

        if (! $user->isSuperAdmin()) {
            $companies->whereKey($user->company_id);
            $users->where('company_id', $user->company_id);
            $employees->where('company_id', $user->company_id);
            $payrolls->where('company_id', $user->company_id);
            $periods->where('company_id', $user->company_id);
        }

        if ($user->isEmployee()) {
            $employeeId = $user->employee?->id;
            $employees->whereKey($employeeId ?? 0);
            $payrolls->where('employee_id', $employeeId ?? 0);
        }

        return Inertia::render('Dashboard', [
            'stats' => [
                'companies' => $user->isSuperAdmin() ? $companies->count() : 1,
                'users' => $users->count(),
                'employees' => $employees->count(),
                'payrolls' => $payrolls->count(),
                'paid_payrolls' => (clone $payrolls)->where('status', PayrollStatus::Paid->value)->count(),
                'failed_payrolls' => (clone $payrolls)->where('status', PayrollStatus::Failed->value)->count(),
                'net_amount' => (float) (clone $payrolls)->sum('net_amount'),
                'open_periods' => $periods->where('status', 'open')->count(),
            ],
            'recent_payrolls' => Payroll::query()
                ->with(['employee.user', 'company', 'period'])
                ->when(! $user->isSuperAdmin(), fn ($q) => $q->where('company_id', $user->company_id))
                ->when($user->isEmployee(), fn ($q) => $q->where('employee_id', $user->employee?->id ?? 0))
                ->latest()
                ->limit(8)
                ->get(),
        ]);
    }
}
