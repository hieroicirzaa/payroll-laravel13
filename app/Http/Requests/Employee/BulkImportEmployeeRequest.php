<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;

class BulkImportEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() || $this->user()?->isAdminCompany();
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:xlsx', 'max:20480'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.mimes' => 'File harus berformat Excel .xlsx.',
            'file.max' => 'Ukuran file maksimal 20 MB.',
        ];
    }
}
