<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        // Basic Profile Info
        'phone',
        'date_of_birth',
        'bio',
        'website',
        'linkedin_url',
        'github_url',
        // Professional Info
        'current_job_title',
        'current_company',
        'industry',
        'years_of_experience',
        'skills',
        'career_summary',
        // Job Preferences
        'preferred_job_type',
        'remote_work_preference',
        'preferred_location',
        'salary_expectation_min',
        'salary_expectation_max',
        'currency',
        'willing_to_relocate',
        'max_travel_distance',
        'open_to_management',
        // Education
        'highest_education',
        'field_of_study',
        'university',
        'graduation_year',
        'certifications',
        // Contact Preferences
        'email_notifications',
        'job_alerts',
        'preferred_contact_times',
        // Additional Info
        'languages',
        'availability',
        'additional_notes',
        // Profile Completion
        'profile_completed',
        'profile_updated_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'date_of_birth' => 'date',
            'skills' => 'array',
            'certifications' => 'array',
            'preferred_contact_times' => 'array',
            'languages' => 'array',
            'remote_work_preference' => 'boolean',
            'willing_to_relocate' => 'boolean',
            'open_to_management' => 'boolean',
            'email_notifications' => 'boolean',
            'job_alerts' => 'boolean',
            'profile_completed' => 'boolean',
            'profile_updated_at' => 'datetime',
        ];
    }

    /**
     * Get the user job views for the user.
     */
    public function jobViews()
    {
        return $this->hasMany(UserJobView::class);
    }

    /**
     * Get the job queue items for the user.
     */
    public function jobQueues()
    {
        return $this->hasMany(JobQueue::class);
    }

    /**
     * Get the job favorites for the user.
     */
    public function jobFavorites()
    {
        return $this->hasMany(UserJobFavorite::class);
    }
}
