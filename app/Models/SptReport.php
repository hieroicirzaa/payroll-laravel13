<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SptReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'employee_id',
        'year',
        'total_gross_amount',
        'total_tax_amount',
        'status',
        'generated_by',
        'generated_at',
        'file_disk',
        'file_path',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'total_gross_amount' => 'decimal:2',
            'total_tax_amount' => 'decimal:2',
            'generated_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
