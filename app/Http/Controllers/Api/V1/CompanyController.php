<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Audit\AuditLogger;
use App\Domain\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Company\StoreCompanyAdminRequest;
use App\Http\Requests\Company\StoreCompanyRequest;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => Company::query()->latest()->paginate(15),
        ]);
    }

    public function store(StoreCompanyRequest $request, AuditLogger $auditLogger): JsonResponse
    {
        $company = Company::create($request->validated());
        $auditLogger->log($request, 'company.created', $company);

        return response()->json([
            'message' => 'Company berhasil dibuat.',
            'data' => $company,
        ], 201);
    }

    public function show(Company $company): JsonResponse
    {
        return response()->json([
            'data' => $company->loadCount('employees', 'users'),
        ]);
    }

    public function update(Request $request, Company $company, AuditLogger $auditLogger): JsonResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'tax_number' => ['nullable', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $company->update($validated);
        $auditLogger->log($request, 'company.updated', $company);

        return response()->json([
            'message' => 'Company berhasil diperbarui.',
            'data' => $company,
        ]);
    }

    public function storeAdmin(StoreCompanyAdminRequest $request, Company $company, AuditLogger $auditLogger): JsonResponse
    {
        $admin = User::create([
            'company_id' => $company->id,
            'name' => $request->string('name'),
            'email' => $request->string('email')->lower(),
            'password' => $request->string('password'),
            'role' => UserRole::AdminCompany,
            'is_active' => true,
        ]);

        $auditLogger->log($request, 'company.admin.created', $admin, ['company_id' => $company->id]);

        return response()->json([
            'message' => 'Admin company berhasil dibuat.',
            'data' => $admin,
        ], 201);
    }

    public function destroy(Request $request, Company $company, AuditLogger $auditLogger): JsonResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $company->update(['is_active' => false]);
        $company->users()->update(['is_active' => false]);
        $company->employees()->update(['status' => 'inactive']);

        foreach ($company->users as $user) {
            $user->tokens()->delete();
            $user->refreshTokens()->delete();
        }

        $auditLogger->log($request, 'company.deactivated', $company);

        return response()->json([
            'message' => 'Company berhasil dinonaktifkan beserta user di dalamnya.',
        ]);
    }
}
