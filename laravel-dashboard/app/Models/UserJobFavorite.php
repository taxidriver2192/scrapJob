<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserJobFavorite extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'job_id',
        'favorited_at',
    ];

    protected $casts = [
        'favorited_at' => 'datetime',
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
     * Add a job to user's favorites
     */
    public static function addFavorite(int $userId, int $jobId): void
    {
        static::updateOrCreate(
            [
                'user_id' => $userId,
                'job_id' => $jobId,
            ],
            [
                'favorited_at' => now(),
            ]
        );
    }

    /**
     * Remove a job from user's favorites
     */
    public static function removeFavorite(int $userId, int $jobId): void
    {
        static::where('user_id', $userId)
            ->where('job_id', $jobId)
            ->delete();
    }

    /**
     * Check if a user has favorited a specific job
     */
    public static function isFavorited(int $userId, int $jobId): bool
    {
        return static::where('user_id', $userId)
            ->where('job_id', $jobId)
            ->exists();
    }

    /**
     * Get all job IDs favorited by a user
     */
    public static function getFavoriteJobIds(int $userId): array
    {
        return static::where('user_id', $userId)
            ->pluck('job_id')
            ->toArray();
    }

    /**
     * Get favorited jobs with pagination
     */
    public static function getFavoriteJobs(int $userId, int $perPage = 10)
    {
        return JobPosting::whereIn('job_id', static::getFavoriteJobIds($userId))
            ->with(['company', 'jobRatings'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}
