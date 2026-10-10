<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'date',
        'clock_in',
        'clock_out',
        'status',
        'work_hours',
        'overtime_hours',
        'ip_address',
        'clock_in_location',
        'notes',
        'is_adjusted',
        'adjusted_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'clock_in' => 'datetime',
            'clock_out' => 'datetime',
            'work_hours' => 'decimal:2',
            'overtime_hours' => 'decimal:2',
            'is_adjusted' => 'boolean',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function adjuster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adjusted_by');
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'present' => 'bg-success',
            'late' => 'bg-warning text-dark',
            'half_day' => 'bg-info text-dark',
            'absent' => 'bg-danger',
            'on_leave' => 'bg-primary',
            'holiday' => 'bg-secondary',
            default => 'bg-light text-dark',
        };
    }
}
