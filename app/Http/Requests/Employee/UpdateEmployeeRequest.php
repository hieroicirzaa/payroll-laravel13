<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() || $this->user()?->isAdminCompany();
    }

    public function rules(): array
    {
        $employeeId = $this->route('employee')?->id;

        return [
            'employee_number' => ['sometimes', 'required', 'string', 'max:80', Rule::unique('employees', 'employee_number')->ignore($employeeId)],
            'position' => ['sometimes', 'required', 'string', 'max:120'],
            'department' => ['nullable', 'string', 'max:120'],
            'employment_status' => ['sometimes', 'required', 'string', 'max:50'],
            'basic_salary' => ['sometimes', 'required', 'numeric', 'min:0'],
            'nik' => ['nullable', 'string', 'max:100'],
            'npwp' => ['nullable', 'string', 'max:100'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account_number' => ['nullable', 'string', 'max:100'],
            'bank_account_name' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'in:active,inactive,resigned'],
        ];
    }
}
