<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobRating extends Model
{
    use HasFactory;

    /**
     * Use job_ratings table for AI ratings now that ai_job_ratings table is removed.
     */
    protected $table = 'job_ratings';
    protected $primaryKey = 'rating_id';

    protected $fillable = [
        'job_id',
        'user_id',
        'prompt',
        'response',
        'model',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'cost',
        'metadata',
        'rated_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'rated_at' => 'datetime',
        'cost' => 'decimal:6',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
