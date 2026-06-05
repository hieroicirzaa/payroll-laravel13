<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Audit\AuditLogger;
use App\Application\Employees\CompanyScope;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payroll\AssignSalaryComponentRequest;
use App\Models\Employee;
use App\Models\EmployeeSalaryComponent;
use App\Models\SalaryComponent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SalaryComponentController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => SalaryComponent::query()->where('is_active', true)->orderBy('type')->orderBy('name')->get(),
        ]);
    }

    public function assign(AssignSalaryComponentRequest $request, Employee $employee, CompanyScope $scope, AuditLogger $auditLogger): JsonResponse
    {
        $scope->assertEmployeeAccessible($request->user(), $employee);
        abort_if($request->user()->isEmployee(), 403);

        $component = EmployeeSalaryComponent::create([
            'company_id' => $employee->company_id,
            'employee_id' => $employee->id,
            'salary_component_id' => $request->integer('salary_component_id'),
            'amount' => $request->input('amount'),
            'is_recurring' => $request->boolean('is_recurring', true),
            'effective_from' => $request->input('effective_from'),
            'effective_until' => $request->input('effective_until'),
        ]);

        $auditLogger->log($request, 'employee.salary_component.assigned', $component);

        return response()->json([
            'message' => 'Komponen gaji berhasil ditambahkan.',
            'data' => $component->load('component'),
        ], 201);
    }
}
