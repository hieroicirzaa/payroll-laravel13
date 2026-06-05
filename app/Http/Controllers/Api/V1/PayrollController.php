<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Audit\AuditLogger;
use App\Domain\Enums\PayrollStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payroll\MarkPayrollFailedRequest;
use App\Models\Payroll;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayrollController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Payroll::query()
            ->with('employee.user', 'period')
            ->latest();

        if ($request->user()->isEmployee()) {
            $query->where('employee_id', $request->user()->employee?->id);
        } elseif (! $request->user()->isSuperAdmin()) {
            $query->where('company_id', $request->user()->company_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return response()->json(['data' => $query->paginate(15)]);
    }

    public function show(Request $request, Payroll $payroll): JsonResponse
    {
        $this->authorizePayroll($request, $payroll);

        return response()->json([
            'data' => $payroll->load('employee.user', 'period', 'items'),
        ]);
    }

    public function markPaid(Request $request, Payroll $payroll, AuditLogger $auditLogger): JsonResponse
    {
        $this->authorizePayrollAdmin($request, $payroll);

        $payroll->update([
            'status' => PayrollStatus::Paid,
            'paid_at' => now(),
            'failure_reason' => null,
            'processed_by' => $request->user()->id,
        ]);

        $auditLogger->log($request, 'payroll.mark_paid', $payroll);

        return response()->json(['message' => 'Payroll ditandai berhasil dibayar.', 'data' => $payroll]);
    }

    public function markFailed(MarkPayrollFailedRequest $request, Payroll $payroll, AuditLogger $auditLogger): JsonResponse
    {
        $this->authorizePayrollAdmin($request, $payroll);

        $payroll->update([
            'status' => PayrollStatus::Failed,
            'failure_reason' => $request->string('failure_reason'),
            'processed_by' => $request->user()->id,
        ]);

        $auditLogger->log($request, 'payroll.mark_failed', $payroll, ['reason' => $request->string('failure_reason')]);

        return response()->json(['message' => 'Payroll ditandai gagal.', 'data' => $payroll]);
    }

    private function authorizePayroll(Request $request, Payroll $payroll): void
    {
        $user = $request->user();

        if ($user->isSuperAdmin()) {
            return;
        }

        if ($user->isAdminCompany() && $user->company_id === $payroll->company_id) {
            return;
        }

        if ($user->isEmployee() && $user->employee?->id === $payroll->employee_id) {
            return;
        }

        abort(403, 'Payroll tidak berada dalam scope akses Anda.');
    }

    private function authorizePayrollAdmin(Request $request, Payroll $payroll): void
    {
        $this->authorizePayroll($request, $payroll);
        abort_if($request->user()->isEmployee(), 403);
    }
}
