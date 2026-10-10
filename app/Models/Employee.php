<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'employee_code',
        'first_name',
        'last_name',
        'gender',
        'date_of_birth',
        'national_id',
        'phone',
        'emergency_contact_name',
        'emergency_contact_phone',
        'address',
        'department_id',
        'designation_id',
        'manager_id',
        'shift_id',
        'employment_type',
        'employment_status',
        'join_date',
        'probation_end_date',
        'termination_date',
        'bank_name',
        'bank_account_number',
        'bank_holder_name',
        'tax_id',
        'custom_attributes',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'join_date' => 'date',
            'probation_end_date' => 'date',
            'termination_date' => 'date',
            'custom_attributes' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(Employee::class, 'manager_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function salaryStructure(): HasOne
    {
        return $this->hasOne(SalaryStructure::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    public function performanceReviews(): HasMany
    {
        return $this->hasMany(PerformanceReview::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getAvatarUrlAttribute(): string
    {
        if ($this->user && $this->user->avatar) {
            return asset($this->user->avatar);
        }

        $initials = strtoupper(substr($this->first_name, 0, 1) . substr($this->last_name, 0, 1));
        $svg = "<svg xmlns='http://www.w3.org/2000/svg' width='100' height='100' viewBox='0 0 100 100'><rect width='100' height='100' fill='#0d6efd'/><text x='50%' y='55%' dominant-baseline='middle' text-anchor='middle' font-family='sans-serif' font-size='38' font-weight='bold' fill='#ffffff'>{$initials}</text></svg>";
        return 'data:image/svg+xml;utf8,' . rawurlencode($svg);
    }
}
