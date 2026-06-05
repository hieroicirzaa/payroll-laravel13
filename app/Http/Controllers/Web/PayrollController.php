<?php

namespace App\Http\Controllers\Web;

use App\Application\Payroll\GeneratePayrollAction;
use App\Application\Payroll\PayrollSlipPdfService;
use App\Domain\Enums\PayrollStatus;
use App\Http\Controllers\Controller;
use App\Mail\PayrollSlipMail;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PayrollPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PayrollController extends Controller
{
    public function index(): Response
    {
        $actor = request()->user();

        return Inertia::render('Payroll/Index', [
            'periods' => PayrollPeriod::query()
                ->with('company')
                ->withCount('payrolls')
                ->when(! $actor->isSuperAdmin(), fn ($q) => $q->where('company_id', $actor->company_id))
                ->latest()
                ->get(),
            'payrolls' => Payroll::query()
                ->with(['company', 'employee.user', 'period', 'items'])
                ->when(! $actor->isSuperAdmin(), fn ($q) => $q->where('company_id', $actor->company_id))
                ->when($actor->isEmployee(), fn ($q) => $q->where('employee_id', $actor->employee?->id ?? 0))
                ->latest()
                ->limit(200)
                ->get(),
            'companies' => Company::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']),
        ]);
    }

    public function storePeriod(Request $request): RedirectResponse
    {
        $actor = $request->user();

        $data = $request->validate([
            'company_id' => ['nullable', 'exists:companies,id'],
            'name' => ['required', 'string', 'max:255'],
            'period_month' => ['required', 'integer', 'between:1,12'],
            'period_year' => ['required', 'integer', 'between:2020,2100'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'status' => ['required', Rule::in(['open', 'generated', 'closed'])],
        ]);

        $companyId = $actor->isSuperAdmin() ? $data['company_id'] : $actor->company_id;
        abort_if(! $companyId, 422, 'company_id wajib diisi.');

        PayrollPeriod::create(array_merge($data, ['company_id' => $companyId]));

        return back()->with('success', 'Periode payroll berhasil dibuat.');
    }

    public function updatePeriod(Request $request, PayrollPeriod $period): RedirectResponse
    {
        $this->assertPeriodScope($period);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'period_month' => ['required', 'integer', 'between:1,12'],
            'period_year' => ['required', 'integer', 'between:2020,2100'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'status' => ['required', Rule::in(['open', 'generated', 'closed'])],
        ]);

        $period->update($data);

        return back()->with('success', 'Periode payroll berhasil diperbarui.');
    }

    public function destroyPeriod(PayrollPeriod $period): RedirectResponse
    {
        $this->assertPeriodScope($period);

        abort_if($period->payrolls()->exists(), 422, 'Periode yang sudah memiliki payroll tidak boleh dihapus.');
        $period->delete();

        return back()->with('success', 'Periode payroll berhasil dihapus.');
    }

    public function generate(PayrollPeriod $period, GeneratePayrollAction $action): RedirectResponse
    {
        $this->assertPeriodScope($period);
        $result = $action->execute($period, request()->user());

        return back()->with('success', "Payroll dibuat: {$result['created']}, diperbarui: {$result['updated']}.");
    }

    public function markPaid(Payroll $payroll): RedirectResponse
    {
        $this->assertPayrollScope($payroll);

        $payroll->update([
            'status' => PayrollStatus::Paid,
            'paid_at' => now(),
            'failure_reason' => null,
            'processed_by' => request()->user()->id,
        ]);

        return back()->with('success', 'Payroll ditandai berhasil dibayar.');
    }

    public function markFailed(Request $request, Payroll $payroll): RedirectResponse
    {
        $this->assertPayrollScope($payroll);

        $data = $request->validate(['failure_reason' => ['required', 'string', 'max:1000']]);

        $payroll->update([
            'status' => PayrollStatus::Failed,
            'failure_reason' => $data['failure_reason'],
            'processed_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Payroll ditandai gagal.');
    }

    public function destroyPayroll(Payroll $payroll): RedirectResponse
    {
        $this->assertPayrollScope($payroll);
        abort_if($payroll->status === PayrollStatus::Paid, 422, 'Payroll yang sudah dibayar tidak boleh dihapus.');

        $payroll->items()->delete();
        $payroll->delete();

        return back()->with('success', 'Payroll draft/gagal berhasil dihapus.');
    }

    public function slip(Payroll $payroll): Response
    {
        $this->assertPayrollScope($payroll);

        return Inertia::render('Payroll/Slip', [
            'payroll' => $payroll->load(['company', 'employee.user', 'period', 'items']),
        ]);
    }

    public function downloadSlipPdf(Payroll $payroll, PayrollSlipPdfService $pdfService): HttpResponse
    {
        $this->assertPayrollScope($payroll);

        $filename = $pdfService->filename($payroll);

        return response($pdfService->output($payroll), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function emailSlip(Payroll $payroll, PayrollSlipPdfService $pdfService): RedirectResponse
    {
        $this->assertPayrollScope($payroll);

        $payroll->loadMissing(['company', 'employee.user', 'period']);
        $email = $payroll->employee?->user?->email;
        abort_if(blank($email), 422, 'Email karyawan tidak tersedia.');

        $filename = $pdfService->filename($payroll);
        $pdfBinary = $pdfService->output($payroll);

        Mail::to($email)->send(new PayrollSlipMail($payroll, $pdfBinary, $filename));

        return back()->with('success', 'Slip gaji berhasil dikirim ke email karyawan.');
    }

    private function assertPeriodScope(PayrollPeriod $period): void
    {
        $actor = request()->user();
        abort_if(! $actor->isSuperAdmin() && $actor->company_id !== $period->company_id, 403);
        abort_if($actor->isEmployee(), 403);
    }

    private function assertPayrollScope(Payroll $payroll): void
    {
        $actor = request()->user();

        if ($actor->isSuperAdmin()) {
            return;
        }

        if ($actor->isAdminCompany() && $actor->company_id === $payroll->company_id) {
            return;
        }

        if ($actor->isEmployee() && $actor->employee?->id === $payroll->employee_id) {
            return;
        }

        abort(403);
    }
}
