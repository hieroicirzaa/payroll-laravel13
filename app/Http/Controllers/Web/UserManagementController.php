<?php

namespace App\Http\Controllers\Web;

use App\Domain\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class UserManagementController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Users/Index', [
            'users' => User::query()->with('company')->latest()->get(),
            'companies' => Company::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']),
            'roles' => collect(UserRole::cases())->map(fn ($role) => ['value' => $role->value, 'label' => $role->label()])->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(array_map(fn ($role) => $role->value, UserRole::cases()))],
            'company_id' => ['nullable', 'required_unless:role,'.UserRole::SuperAdmin->value, 'exists:companies,id'],
            'password' => ['required', 'confirmed', Password::min(10)->mixedCase()->numbers()],
            'is_active' => ['boolean'],
        ]);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'company_id' => $data['role'] === UserRole::SuperAdmin->value ? null : $data['company_id'],
            'password' => Hash::make($data['password']),
            'is_active' => $data['is_active'] ?? true,
        ]);

        return back()->with('success', 'User berhasil dibuat.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'role' => ['required', Rule::in(array_map(fn ($role) => $role->value, UserRole::cases()))],
            'company_id' => ['nullable', 'required_unless:role,'.UserRole::SuperAdmin->value, 'exists:companies,id'],
            'password' => ['nullable', 'confirmed', Password::min(10)->mixedCase()->numbers()],
            'is_active' => ['boolean'],
        ]);

        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'company_id' => $data['role'] === UserRole::SuperAdmin->value ? null : $data['company_id'],
            'is_active' => $data['is_active'] ?? false,
        ];

        if (! empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }

        $user->update($payload);

        return back()->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_if($user->id === request()->user()->id, 422, 'Akun sendiri tidak boleh dinonaktifkan melalui menu ini.');

        $user->update(['is_active' => false]);
        $user->employee?->update(['status' => 'inactive']);

        return back()->with('success', 'User berhasil dinonaktifkan.');
    }

    public function restore(User $user): RedirectResponse
    {
        $user->update(['is_active' => true]);

        return back()->with('success', 'User berhasil diaktifkan ulang.');
    }
}
