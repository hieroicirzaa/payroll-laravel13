<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CompanyController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Companies/Index', [
            'companies' => Company::query()->withCount(['users', 'employees'])->latest()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:40', 'alpha_dash', 'unique:companies,code'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'tax_number' => ['nullable', 'string', 'max:80'],
            'is_active' => ['boolean'],
        ]);

        Company::create($data);

        return back()->with('success', 'Company berhasil dibuat.');
    }

    public function update(Request $request, Company $company): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:40', 'alpha_dash', Rule::unique('companies', 'code')->ignore($company)],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'tax_number' => ['nullable', 'string', 'max:80'],
            'is_active' => ['boolean'],
        ]);

        $company->update($data);

        return back()->with('success', 'Company berhasil diperbarui.');
    }

    public function destroy(Company $company): RedirectResponse
    {
        $company->update(['is_active' => false]);
        $company->users()->update(['is_active' => false]);
        $company->employees()->update(['status' => 'inactive']);

        return back()->with('success', 'Company dinonaktifkan beserta akun di bawahnya.');
    }

    public function restore(Company $company): RedirectResponse
    {
        $company->update(['is_active' => true]);

        return back()->with('success', 'Company berhasil diaktifkan ulang.');
    }
}
