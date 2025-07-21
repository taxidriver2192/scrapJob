<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\JobRating;

class JobQueue extends Model
{
    protected $table = 'job_queue';
    protected $primaryKey = 'queue_id';

    protected $fillable = [
        'job_id',
        'user_id',
        'queued_at',
        'status_code',
    ];

    protected $casts = [
        'queued_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Status constants
    const STATUS_PENDING = 1;
    const STATUS_IN_PROGRESS = 2;
    const STATUS_DONE = 3;
    const STATUS_ERROR = 4;

    public function jobPosting()
    {
        return $this->belongsTo(JobPosting::class, 'job_id', 'job_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getStatusTextAttribute()
    {
        return match($this->status_code) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_DONE => 'Done',
            self::STATUS_ERROR => 'Error',
            default => 'Unknown',
        };
    }
}
