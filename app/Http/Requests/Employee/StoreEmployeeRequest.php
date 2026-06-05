<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() || $this->user()?->isAdminCompany();
    }

    public function rules(): array
    {
        return [
            'company_id' => ['nullable', 'exists:companies,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'employee_number' => ['required', 'string', 'max:80', 'unique:employees,employee_number'],
            'position' => ['required', 'string', 'max:120'],
            'department' => ['nullable', 'string', 'max:120'],
            'join_date' => ['required', 'date'],
            'employment_status' => ['required', 'string', 'max:50'],
            'basic_salary' => ['required', 'numeric', 'min:0'],
            'nik' => ['nullable', 'string', 'max:100'],
            'npwp' => ['nullable', 'string', 'max:100'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account_number' => ['nullable', 'string', 'max:100'],
            'bank_account_name' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:50'],
        ];
    }
}
