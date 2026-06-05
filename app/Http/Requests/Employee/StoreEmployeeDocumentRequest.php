<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() || $this->user()?->isAdminCompany() || $this->user()?->isEmployee();
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['ktp', 'ijazah', 'npwp', 'contract', 'other'])],
            'title' => ['required', 'string', 'max:150'],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }
}
