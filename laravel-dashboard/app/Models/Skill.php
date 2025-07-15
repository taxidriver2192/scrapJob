<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Skill extends Model
{
    use HasFactory;

    protected $table = 'skills';
    protected $primaryKey = 'skill_id';

    protected $fillable = [
        'skill_name',
    ];

    /**
     * Get the job postings that use this skill (many-to-many).
     */
    public function jobPostings()
    {
        return $this->belongsToMany(JobPosting::class, 'job_skills', 'skill_id', 'job_id');
    }
}
