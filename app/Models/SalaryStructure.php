<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryStructure extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'basic_salary',
        'house_allowance',
        'transport_allowance',
        'meal_allowance',
        'other_allowances',
        'tax_deduction',
        'insurance_deduction',
        'other_deductions',
        'effective_date',
    ];

    protected function casts(): array
    {
        return [
            'basic_salary' => 'decimal:2',
            'house_allowance' => 'decimal:2',
            'transport_allowance' => 'decimal:2',
            'meal_allowance' => 'decimal:2',
            'other_allowances' => 'decimal:2',
            'tax_deduction' => 'decimal:2',
            'insurance_deduction' => 'decimal:2',
            'other_deductions' => 'decimal:2',
            'effective_date' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function getTotalAllowanceAttribute(): float
    {
        return (float)$this->house_allowance +
               (float)$this->transport_allowance +
               (float)$this->meal_allowance +
               (float)$this->other_allowances;
    }

    public function getTotalDeductionAttribute(): float
    {
        return (float)$this->tax_deduction +
               (float)$this->insurance_deduction +
               (float)$this->other_deductions;
    }

    public function getGrossSalaryAttribute(): float
    {
        return (float)$this->basic_salary + $this->total_allowance;
    }

    public function getNetSalaryAttribute(): float
    {
        return max(0, $this->gross_salary - $this->total_deduction);
    }
}
