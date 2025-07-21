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
        'apply_url',
        'posted_date',
        'applicants',
        'work_type',
        'skills',
        'openai_adresse',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'posted_date' => 'datetime',
        'skills' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
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
}
