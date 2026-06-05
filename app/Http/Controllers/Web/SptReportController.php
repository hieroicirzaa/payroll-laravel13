<?php

namespace App\Http\Controllers\Web;

use App\Domain\Enums\PayrollStatus;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\SptReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SptReportController extends Controller
{
    public function index(): Response
    {
        $actor = request()->user();

        return Inertia::render('SptReports/Index', [
            'reports' => SptReport::query()
                ->with(['company', 'employee.user'])
                ->when(! $actor->isSuperAdmin(), fn ($q) => $q->where('company_id', $actor->company_id))
                ->when($actor->isEmployee(), fn ($q) => $q->where('employee_id', $actor->employee?->id ?? 0))
                ->latest()
                ->get(),
            'companies' => Company::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']),
            'employees' => Employee::query()->with('user')
                ->when(! $actor->isSuperAdmin(), fn ($q) => $q->where('company_id', $actor->company_id))
                ->orderBy('employee_number')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $actor = $request->user();
        abort_if($actor->isEmployee(), 403);

        $data = $request->validate([
            'company_id' => ['nullable', 'exists:companies,id'],
            'employee_id' => ['nullable', 'exists:employees,id'],
            'year' => ['required', 'integer', 'between:2020,2100'],
            'status' => ['required', Rule::in(['draft', 'final'])],
        ]);

        $companyId = $actor->isSuperAdmin() ? $data['company_id'] : $actor->company_id;
        abort_if(! $companyId, 422, 'company_id wajib diisi.');

        $employees = Employee::query()
            ->where('company_id', $companyId)
            ->when($data['employee_id'] ?? null, fn ($q, $id) => $q->whereKey($id))
            ->get();

        foreach ($employees as $employee) {
            $payrolls = Payroll::query()
                ->where('company_id', $companyId)
                ->where('employee_id', $employee->id)
                ->where('status', PayrollStatus::Paid->value)
                ->whereHas('period', fn ($q) => $q->where('period_year', $data['year']))
                ->get();

            SptReport::updateOrCreate(
                ['company_id' => $companyId, 'employee_id' => $employee->id, 'year' => $data['year']],
                [
                    'total_gross_amount' => $payrolls->sum('gross_amount'),
                    'total_tax_amount' => $payrolls->sum('tax_amount'),
                    'status' => $data['status'],
                    'generated_by' => $actor->id,
                    'generated_at' => now(),
                ]
            );
        }

        return back()->with('success', 'Laporan SPT berhasil dibuat/diperbarui dari payroll berstatus paid.');
    }

    public function update(Request $request, SptReport $sptReport): RedirectResponse
    {
        $this->assertScope($sptReport);
        $data = $request->validate(['status' => ['required', Rule::in(['draft', 'final', 'submitted'])]]);
        $sptReport->update($data);

        return back()->with('success', 'Status laporan SPT berhasil diperbarui.');
    }

    public function destroy(SptReport $sptReport): RedirectResponse
    {
        $this->assertScope($sptReport);
        $sptReport->delete();

        return back()->with('success', 'Laporan SPT berhasil dihapus.');
    }

    private function assertScope(SptReport $report): void
    {
        $actor = request()->user();
        abort_if($actor->isEmployee(), 403);
        abort_if(! $actor->isSuperAdmin() && $actor->company_id !== $report->company_id, 403);
    }
}
