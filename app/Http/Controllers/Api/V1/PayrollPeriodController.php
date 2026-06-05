<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Audit\AuditLogger;
use App\Application\Employees\CompanyScope;
use App\Application\Payroll\GeneratePayrollAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payroll\StorePayrollPeriodRequest;
use App\Models\PayrollPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayrollPeriodController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = PayrollPeriod::query()->with('company')->latest();

        if (! $request->user()->isSuperAdmin()) {
            $query->where('company_id', $request->user()->company_id);
        }

        return response()->json(['data' => $query->paginate(15)]);
    }

    public function store(StorePayrollPeriodRequest $request, CompanyScope $scope, AuditLogger $auditLogger): JsonResponse
    {
        $companyId = $scope->targetCompanyId($request->user(), $request->integer('company_id') ?: null);

        $period = PayrollPeriod::create([
            'company_id' => $companyId,
            'name' => $request->string('name'),
            'period_month' => $request->integer('period_month'),
            'period_year' => $request->integer('period_year'),
            'start_date' => $request->date('start_date'),
            'end_date' => $request->date('end_date'),
            'status' => 'open',
        ]);

        $auditLogger->log($request, 'payroll_period.created', $period);

        return response()->json([
            'message' => 'Periode payroll berhasil dibuat.',
            'data' => $period,
        ], 201);
    }

    public function generate(Request $request, PayrollPeriod $period, GeneratePayrollAction $action, AuditLogger $auditLogger): JsonResponse
    {
        abort_unless($request->user()->isSuperAdmin() || $request->user()->isAdminCompany(), 403);

        if (! $request->user()->isSuperAdmin() && $request->user()->company_id !== $period->company_id) {
            abort(403, 'Periode payroll berada di company lain.');
        }

        $result = $action->execute($period, $request->user());
        $auditLogger->log($request, 'payroll_period.generated', $period, $result);

        return response()->json([
            'message' => 'Payroll berhasil digenerate.',
            'data' => $result,
        ]);
    }
}
