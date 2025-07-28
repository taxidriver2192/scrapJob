<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

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
        'city',
        'zipcode',
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

    public function userJobViews()
    {
        return $this->hasMany(\App\Models\UserJobView::class, 'job_id', 'job_id');
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

    /** 55‑60 tegn lang sidetitel */
    public function getSeoTitleAttribute(): string
    {
        $role     = $this->title ?: 'Medarbejder';          // neutral rolle
        $mainTech = $this->skills[0] ?? null;
        $company  = optional($this->company)->name ?: 'Vores kunde';
        $city     = $this->city ?: null;
        $usp      = $this->company->industrydesc
            ?? ($this->work_type === 'remote' ? 'Remote først' : null);

        $parts = [
            trim($role . ($mainTech ? " $mainTech" : '')),
            '–',
            $company,
            $city ? ", $city" : null,
            $usp ? " | $usp" : null,
        ];

        return Str::limit(trim(collect($parts)->filter()->implode(' ')), 60, '');
    }

    /** 150‑160 tegn lang meta‑beskrivelse */
    public function getSeoSubtitleAttribute(): string
    {
        $techList = collect($this->skills)->take(3)->join(', ') ?: 'relevante kompetencer';
        $benefit  = $this->company->companydesc ?: 'en fleksibel arbejdskultur';

        return Str::limit(sprintf(
            'Bliv en del af %s som %s. Bidrag med %s og nyd %s.',
            optional($this->company)->name ?: 'vores team',
            Str::lower($this->title ?: 'medarbejder'),
            $techList,
            Str::lower($benefit)
        ), 160, '');
    }
}
