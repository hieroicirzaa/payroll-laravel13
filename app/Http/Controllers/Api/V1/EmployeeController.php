<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Audit\AuditLogger;
use App\Application\Employees\CompanyScope;
use App\Domain\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreEmployeeRequest;
use App\Http\Requests\Employee\UpdateEmployeeRequest;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployeeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Employee::query()->with('user', 'company')->latest();

        if (! $request->user()->isSuperAdmin()) {
            $query->where('company_id', $request->user()->company_id);
        }

        return response()->json([
            'data' => $query->paginate(15),
        ]);
    }

    public function store(StoreEmployeeRequest $request, CompanyScope $scope, AuditLogger $auditLogger): JsonResponse
    {
        $companyId = $scope->targetCompanyId($request->user(), $request->integer('company_id') ?: null);

        $employee = DB::transaction(function () use ($request, $companyId) {
            $user = User::create([
                'company_id' => $companyId,
                'name' => $request->string('name'),
                'email' => $request->string('email')->lower(),
                'password' => $request->string('password'),
                'role' => UserRole::Employee,
                'is_active' => true,
            ]);

            return Employee::create([
                'company_id' => $companyId,
                'user_id' => $user->id,
                'employee_number' => $request->string('employee_number'),
                'position' => $request->string('position'),
                'department' => $request->input('department'),
                'join_date' => $request->date('join_date'),
                'employment_status' => $request->string('employment_status'),
                'basic_salary' => $request->input('basic_salary'),
                'nik' => $request->input('nik'),
                'npwp' => $request->input('npwp'),
                'bank_name' => $request->input('bank_name'),
                'bank_account_number' => $request->input('bank_account_number'),
                'bank_account_name' => $request->input('bank_account_name'),
                'address' => $request->input('address'),
                'phone' => $request->input('phone'),
                'status' => 'active',
            ]);
        });

        $auditLogger->log($request, 'employee.created', $employee);

        return response()->json([
            'message' => 'Karyawan berhasil dibuat.',
            'data' => $employee->load('user', 'company'),
        ], 201);
    }

    public function show(Request $request, Employee $employee, CompanyScope $scope): JsonResponse
    {
        $scope->assertEmployeeAccessible($request->user(), $employee);

        return response()->json([
            'data' => $employee->load('user', 'company', 'salaryComponents.component'),
        ]);
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee, CompanyScope $scope, AuditLogger $auditLogger): JsonResponse
    {
        $scope->assertEmployeeAccessible($request->user(), $employee);
        abort_if($request->user()->isEmployee(), 403);

        $employee->update($request->validated());
        $auditLogger->log($request, 'employee.updated', $employee);

        return response()->json([
            'message' => 'Karyawan berhasil diperbarui.',
            'data' => $employee->refresh()->load('user', 'company'),
        ]);
    }

    public function destroy(Request $request, Employee $employee, CompanyScope $scope, AuditLogger $auditLogger): JsonResponse
    {
        $scope->assertEmployeeAccessible($request->user(), $employee);
        abort_if($request->user()->isEmployee(), 403);

        $employee->user()->update(['is_active' => false]);
        $employee->update(['status' => 'inactive']);
        $auditLogger->log($request, 'employee.deactivated', $employee);

        return response()->json(['message' => 'Karyawan dinonaktifkan.']);
    }
}
