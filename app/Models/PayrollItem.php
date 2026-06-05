<?php

namespace App\Models;

use App\Domain\Enums\SalaryComponentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_id',
        'salary_component_id',
        'name',
        'type',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'type' => SalaryComponentType::class,
            'amount' => 'decimal:2',
        ];
    }

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }
}
