<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'days_allowed',
        'is_paid',
        'requires_attachment',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'days_allowed' => 'integer',
            'is_paid' => 'boolean',
            'requires_attachment' => 'boolean',
        ];
    }

    public function balances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function requests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }
}
