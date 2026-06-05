<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'user_id',
        'employee_number',
        'position',
        'department',
        'join_date',
        'employment_status',
        'basic_salary',
        'nik',
        'npwp',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'address',
        'phone',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'join_date' => 'date',
            'basic_salary' => 'decimal:2',
            'nik' => 'encrypted',
            'npwp' => 'encrypted',
            'bank_account_number' => 'encrypted',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    public function salaryComponents(): HasMany
    {
        return $this->hasMany(EmployeeSalaryComponent::class);
    }

    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class);
    }
}
