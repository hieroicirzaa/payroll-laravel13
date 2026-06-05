<?php

namespace App\Http\Controllers\Web;

use App\Domain\Enums\SalaryComponentType;
use App\Http\Controllers\Controller;
use App\Models\SalaryComponent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SalaryComponentController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('SalaryComponents/Index', [
            'components' => SalaryComponent::query()->latest()->get(),
            'types' => [
                ['value' => SalaryComponentType::Earning->value, 'label' => 'Penambah'],
                ['value' => SalaryComponentType::Deduction->value, 'label' => 'Potongan'],
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:80', 'alpha_dash', 'unique:salary_components,code'],
            'type' => ['required', Rule::in(array_map(fn ($type) => $type->value, SalaryComponentType::cases()))],
            'is_taxable' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        SalaryComponent::create($data);

        return back()->with('success', 'Komponen gaji berhasil dibuat.');
    }

    public function update(Request $request, SalaryComponent $salaryComponent): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:80', 'alpha_dash', Rule::unique('salary_components', 'code')->ignore($salaryComponent)],
            'type' => ['required', Rule::in(array_map(fn ($type) => $type->value, SalaryComponentType::cases()))],
            'is_taxable' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        $salaryComponent->update($data);

        return back()->with('success', 'Komponen gaji berhasil diperbarui.');
    }

    public function destroy(SalaryComponent $salaryComponent): RedirectResponse
    {
        $salaryComponent->update(['is_active' => false]);

        return back()->with('success', 'Komponen gaji berhasil dinonaktifkan.');
    }

    public function restore(SalaryComponent $salaryComponent): RedirectResponse
    {
        $salaryComponent->update(['is_active' => true]);

        return back()->with('success', 'Komponen gaji berhasil diaktifkan ulang.');
    }
}
