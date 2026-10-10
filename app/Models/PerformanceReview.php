<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformanceReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'reviewer_id',
        'review_period',
        'technical_rating',
        'communication_rating',
        'teamwork_rating',
        'overall_rating',
        'strengths',
        'areas_for_improvement',
        'goals',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'technical_rating' => 'integer',
            'communication_rating' => 'integer',
            'teamwork_rating' => 'integer',
            'overall_rating' => 'decimal:2',
            'goals' => 'array',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'completed' => 'bg-success',
            'submitted' => 'bg-info text-dark',
            'draft' => 'bg-secondary',
            default => 'bg-light text-dark',
        };
    }
}
