<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Audit\AuditLogger;
use App\Domain\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $validated = $request->validate([
            'role' => ['nullable', Rule::in(array_map(fn (UserRole $role) => $role->value, UserRole::cases()))],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        $query = User::query()->with(['company:id,name,code', 'employee:id,user_id,employee_number,position,status'])->latest();

        if (! empty($validated['role'])) {
            $query->where('role', $validated['role']);
        }

        if (! empty($validated['company_id'])) {
            $query->where('company_id', $validated['company_id']);
        }

        if (! empty($validated['status'])) {
            $query->where('is_active', $validated['status'] === 'active');
        }

        if (! empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%");
            });
        }

        return response()->json([
            'data' => $query->paginate(15),
        ]);
    }

    public function destroy(Request $request, User $user, AuditLogger $auditLogger): JsonResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);
        abort_if($request->user()->id === $user->id, 422, 'Super admin tidak boleh menonaktifkan akunnya sendiri.');

        $user->update(['is_active' => false]);

        if ($user->employee) {
            $user->employee()->update(['status' => 'inactive']);
        }

        $user->tokens()->delete();
        $user->refreshTokens()->delete();

        $auditLogger->log($request, 'user.deactivated', $user);

        return response()->json([
            'message' => 'User berhasil dinonaktifkan.',
        ]);
    }
}
