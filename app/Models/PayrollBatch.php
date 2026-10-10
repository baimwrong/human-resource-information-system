<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class PayrollBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_number',
        'month',
        'year',
        'total_employees',
        'total_gross',
        'total_deductions',
        'total_net',
        'status',
        'payment_date',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'month' => 'integer',
            'year' => 'integer',
            'total_employees' => 'integer',
            'total_gross' => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'total_net' => 'decimal:2',
            'payment_date' => 'date',
        ];
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getPeriodNameAttribute(): string
    {
        return Carbon::createFromDate($this->year, $this->month, 1)->translatedFormat('F Y');
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'paid' => 'bg-success',
            'processed' => 'bg-info text-dark',
            'draft' => 'bg-warning text-dark',
            default => 'bg-secondary',
        };
    }
}
