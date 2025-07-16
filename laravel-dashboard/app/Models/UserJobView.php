<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserJobView extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'job_id',
        'viewed_at',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function jobPosting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class, 'job_id', 'job_id');
    }

    /**
     * Mark a job as viewed by a user
     */
    public static function markAsViewed(int $userId, string $jobId): void
    {
        static::updateOrCreate(
            [
                'user_id' => $userId,
                'job_id' => $jobId,
            ],
            [
                'viewed_at' => now(),
            ]
        );
    }

    /**
     * Check if a user has viewed a specific job
     */
    public static function hasUserViewed(int $userId, string $jobId): bool
    {
        return static::where('user_id', $userId)
            ->where('job_id', $jobId)
            ->exists();
    }

    /**
     * Get all job IDs viewed by a user
     */
    public static function getViewedJobIds(int $userId): array
    {
        return static::where('user_id', $userId)
            ->pluck('job_id')
            ->toArray();
    }
}
