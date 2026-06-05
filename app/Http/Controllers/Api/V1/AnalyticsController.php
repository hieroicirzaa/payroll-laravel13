<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Payroll;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function payrollSummary(Request $request): JsonResponse
    {
        $payrolls = Payroll::query();
        $employees = Employee::query();

        if (! $request->user()->isSuperAdmin()) {
            $payrolls->where('company_id', $request->user()->company_id);
            $employees->where('company_id', $request->user()->company_id);
        }

        return response()->json([
            'data' => [
                'employees' => (clone $employees)->count(),
                'active_employees' => (clone $employees)->where('status', 'active')->count(),
                'payroll_paid' => (clone $payrolls)->where('status', 'paid')->count(),
                'payroll_failed' => (clone $payrolls)->where('status', 'failed')->count(),
                'total_net_paid' => (float) (clone $payrolls)->where('status', 'paid')->sum('net_amount'),
                'total_gross' => (float) (clone $payrolls)->sum('gross_amount'),
            ],
        ]);
    }

    public function companyDashboard(Request $request): JsonResponse
    {
        if ($request->user()->isSuperAdmin()) {
            $companies = Company::query()->withCount('employees')->latest()->limit(8)->get();
        } else {
            $companies = Company::query()->whereKey($request->user()->company_id)->withCount('employees')->get();
        }

        $statusBreakdown = Payroll::query()
            ->when(! $request->user()->isSuperAdmin(), fn ($q) => $q->where('company_id', $request->user()->company_id))
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get();

        return response()->json([
            'data' => [
                'companies' => $companies,
                'payroll_status_breakdown' => $statusBreakdown,
            ],
        ]);
    }
}
