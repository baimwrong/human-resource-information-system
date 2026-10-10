<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Payslip extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_batch_id',
        'employee_id',
        'slip_number',
        'month',
        'year',
        'basic_salary',
        'total_allowance',
        'total_deduction',
        'net_salary',
        'breakdown',
        'payment_status',
        'paid_at',
        'payment_method',
    ];

    protected function casts(): array
    {
        return [
            'month' => 'integer',
            'year' => 'integer',
            'basic_salary' => 'decimal:2',
            'total_allowance' => 'decimal:2',
            'total_deduction' => 'decimal:2',
            'net_salary' => 'decimal:2',
            'breakdown' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    public function payrollBatch(): BelongsTo
    {
        return $this->belongsTo(PayrollBatch::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function getGrossSalaryAttribute(): float
    {
        return (float)$this->basic_salary + (float)$this->total_allowance;
    }

    public function getPeriodNameAttribute(): string
    {
        return Carbon::createFromDate($this->year, $this->month, 1)->translatedFormat('F Y');
    }
}
