<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $table = 'companies';
    protected $primaryKey = 'company_id';

    protected $fillable = [
        'name',
    ];

    public function jobPostings()
    {
        return $this->hasMany(JobPosting::class, 'company_id', 'company_id');
    }
}
