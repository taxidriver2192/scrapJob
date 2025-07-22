<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobPosting extends Model
{
    protected $table = 'job_postings';
    protected $primaryKey = 'job_id';

    protected $fillable = [
        'linkedin_job_id',
        'title',
        'company_id',
        'location',
        'description',
        'brief_summary_of_job',
        'apply_url',
        'posted_date',
        'applicants',
        'work_type',
        'skills',
        'openai_adresse',
        'latitude',
        'longitude',
        'job_post_closed_date',
    ];

    protected $casts = [
        'posted_date' => 'datetime',
        'skills' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'job_post_closed_date' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'company_id');
    }

    public function jobRatings()
    {
        return $this->hasMany(JobRating::class, 'job_id', 'job_id');
    }

    public function jobQueue()
    {
        return $this->hasOne(JobQueue::class, 'job_id', 'job_id');
    }

    public function getCompanyNameAttribute()
    {
        return $this->company ? $this->company->name : null;
    }

    /**
     * Check if the job posting is closed
     */
    public function isClosed(): bool
    {
        return !is_null($this->job_post_closed_date);
    }

    /**
     * Check if the job posting is open
     */
    public function isOpen(): bool
    {
        return is_null($this->job_post_closed_date);
    }
}
