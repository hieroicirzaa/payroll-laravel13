<?php

namespace App\Models;

use App\Domain\Enums\SalaryComponentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalaryComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'type',
        'is_taxable',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => SalaryComponentType::class,
            'is_taxable' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
