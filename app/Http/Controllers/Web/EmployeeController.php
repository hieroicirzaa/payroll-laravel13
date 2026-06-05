<?php

namespace App\Http\Controllers\Web;

use App\Application\Employees\CompanyScope;
use App\Application\Employees\Exports\EmployeeExportService;
use App\Application\Employees\Imports\EmployeeBulkImportService;
use App\Application\Employees\Imports\EmployeeImportTemplate;
use App\Domain\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\BulkImportEmployeeRequest;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeSalaryComponent;
use App\Models\SalaryComponent;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeController extends Controller
{
    public function __construct(private readonly CompanyScope $scope)
    {
    }

    public function index(): Response
    {
        $actor = request()->user();

        return Inertia::render('Employees/Index', [
            'employees' => Employee::query()
                ->with(['user', 'company', 'salaryComponents.component'])
                ->when(! $actor->isSuperAdmin(), fn ($q) => $q->where('company_id', $actor->company_id))
                ->latest()
                ->get(),
            'companies' => Company::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']),
            'salaryComponents' => SalaryComponent::query()->where('is_active', true)->orderBy('name')->get(),
            'importReport' => session('employee_import_report'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $actor = $request->user();

        $data = $request->validate([
            'company_id' => ['nullable', 'exists:companies,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(10)->mixedCase()->numbers()],
            'employee_number' => ['required', 'string', 'max:80', 'unique:employees,employee_number'],
            'position' => ['required', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'join_date' => ['required', 'date'],
            'employment_status' => ['required', 'string', 'max:80'],
            'basic_salary' => ['required', 'numeric', 'min:0'],
            'nik' => ['nullable', 'string', 'max:80'],
            'npwp' => ['nullable', 'string', 'max:80'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account_number' => ['nullable', 'string', 'max:100'],
            'bank_account_name' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:50'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $companyId = $this->scope->targetCompanyId($actor, $data['company_id'] ?? null);

        DB::transaction(function () use ($data, $companyId) {
            $user = User::create([
                'company_id' => $companyId,
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => UserRole::Employee,
                'is_active' => $data['status'] === 'active',
            ]);

            Employee::create([
                'company_id' => $companyId,
                'user_id' => $user->id,
                'employee_number' => $data['employee_number'],
                'position' => $data['position'],
                'department' => $data['department'] ?? null,
                'join_date' => $data['join_date'],
                'employment_status' => $data['employment_status'],
                'basic_salary' => $data['basic_salary'],
                'nik' => $data['nik'] ?? null,
                'npwp' => $data['npwp'] ?? null,
                'bank_name' => $data['bank_name'] ?? null,
                'bank_account_number' => $data['bank_account_number'] ?? null,
                'bank_account_name' => $data['bank_account_name'] ?? null,
                'address' => $data['address'] ?? null,
                'phone' => $data['phone'] ?? null,
                'status' => $data['status'],
            ]);
        });

        return back()->with('success', 'Karyawan berhasil dibuat.');
    }



    public function downloadImportTemplate(EmployeeImportTemplate $template): StreamedResponse
    {
        return $template->download();
    }

    public function export(EmployeeExportService $exporter): StreamedResponse
    {
        return $exporter->download(request()->user());
    }

    public function import(BulkImportEmployeeRequest $request, EmployeeBulkImportService $importer): RedirectResponse
    {
        $report = $importer->import($request->file('file'), $request->user());

        $message = "Impor selesai. {$report['inserted']} karyawan berhasil dibuat.";
        if ($report['failed'] > 0) {
            $message .= " {$report['failed']} baris ditolak.";
        }

        return back()
            ->with('success', $message)
            ->with('employee_import_report', $report);
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $this->scope->assertEmployeeAccessible($request->user(), $employee);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($employee->user_id)],
            'password' => ['nullable', 'confirmed', Password::min(10)->mixedCase()->numbers()],
            'employee_number' => ['required', 'string', 'max:80', Rule::unique('employees', 'employee_number')->ignore($employee)],
            'position' => ['required', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'join_date' => ['required', 'date'],
            'employment_status' => ['required', 'string', 'max:80'],
            'basic_salary' => ['required', 'numeric', 'min:0'],
            'nik' => ['nullable', 'string', 'max:80'],
            'npwp' => ['nullable', 'string', 'max:80'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account_number' => ['nullable', 'string', 'max:100'],
            'bank_account_name' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:50'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        DB::transaction(function () use ($employee, $data) {
            $userPayload = [
                'name' => $data['name'],
                'email' => $data['email'],
                'is_active' => $data['status'] === 'active',
            ];

            if (! empty($data['password'])) {
                $userPayload['password'] = Hash::make($data['password']);
            }

            $employee->user->update($userPayload);
            $employee->update([
                'employee_number' => $data['employee_number'],
                'position' => $data['position'],
                'department' => $data['department'] ?? null,
                'join_date' => $data['join_date'],
                'employment_status' => $data['employment_status'],
                'basic_salary' => $data['basic_salary'],
                'nik' => $data['nik'] ?? null,
                'npwp' => $data['npwp'] ?? null,
                'bank_name' => $data['bank_name'] ?? null,
                'bank_account_number' => $data['bank_account_number'] ?? null,
                'bank_account_name' => $data['bank_account_name'] ?? null,
                'address' => $data['address'] ?? null,
                'phone' => $data['phone'] ?? null,
                'status' => $data['status'],
            ]);
        });

        return back()->with('success', 'Data karyawan berhasil diperbarui.');
    }

    public function destroy(Request $request, Employee $employee): RedirectResponse
    {
        $this->scope->assertEmployeeAccessible($request->user(), $employee);

        $employee->update(['status' => 'inactive']);
        $employee->user->update(['is_active' => false]);

        return back()->with('success', 'Karyawan berhasil dinonaktifkan.');
    }

    public function restore(Request $request, Employee $employee): RedirectResponse
    {
        $this->scope->assertEmployeeAccessible($request->user(), $employee);

        $employee->update(['status' => 'active']);
        $employee->user->update(['is_active' => true]);

        return back()->with('success', 'Karyawan berhasil diaktifkan ulang.');
    }

    public function assignComponent(Request $request, Employee $employee): RedirectResponse
    {
        $this->scope->assertEmployeeAccessible($request->user(), $employee);

        $data = $request->validate([
            'salary_component_id' => ['required', 'exists:salary_components,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'is_recurring' => ['boolean'],
            'effective_from' => ['nullable', 'date'],
            'effective_until' => ['nullable', 'date', 'after_or_equal:effective_from'],
        ]);

        EmployeeSalaryComponent::updateOrCreate(
            ['employee_id' => $employee->id, 'salary_component_id' => $data['salary_component_id']],
            [
                'company_id' => $employee->company_id,
                'amount' => $data['amount'],
                'is_recurring' => $data['is_recurring'] ?? true,
                'effective_from' => $data['effective_from'] ?? null,
                'effective_until' => $data['effective_until'] ?? null,
            ]
        );

        return back()->with('success', 'Komponen gaji karyawan berhasil disimpan.');
    }

    public function removeComponent(Request $request, EmployeeSalaryComponent $component): RedirectResponse
    {
        $this->scope->assertEmployeeAccessible($request->user(), $component->employee);
        $component->delete();

        return back()->with('success', 'Komponen gaji karyawan berhasil dihapus.');
    }
}
