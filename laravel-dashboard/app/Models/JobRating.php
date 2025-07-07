<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobRating extends Model
{
    protected $table = 'job_ratings';
    protected $primaryKey = 'rating_id';
    
    protected $fillable = [
        'job_id',
        'overall_score',
        'location_score',
        'tech_score',
        'team_size_score',
        'leadership_score',
        'criteria',
        'rating_type',
        'rated_at',
    ];

    protected $casts = [
        'criteria' => 'array',
        'rated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function jobPosting()
    {
        return $this->belongsTo(JobPosting::class, 'job_id', 'job_id');
    }
}
