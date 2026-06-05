<?php

namespace App\Http\Requests\Payroll;

use Illuminate\Foundation\Http\FormRequest;

class MarkPayrollFailedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() || $this->user()?->isAdminCompany();
    }

    public function rules(): array
    {
        return [
            'failure_reason' => ['required', 'string', 'max:500'],
        ];
    }
}
