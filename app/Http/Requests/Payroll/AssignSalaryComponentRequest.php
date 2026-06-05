<?php

namespace App\Http\Requests\Payroll;

use Illuminate\Foundation\Http\FormRequest;

class AssignSalaryComponentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() || $this->user()?->isAdminCompany();
    }

    public function rules(): array
    {
        return [
            'salary_component_id' => ['required', 'exists:salary_components,id'],
            'amount' => ['required', 'numeric'],
            'is_recurring' => ['sometimes', 'boolean'],
            'effective_from' => ['nullable', 'date'],
            'effective_until' => ['nullable', 'date', 'after_or_equal:effective_from'],
        ];
    }
}
