<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $table = 'companies';
    protected $primaryKey = 'company_id';

    protected $fillable = [
        'name',
        'vat','status','address','zipcode','city','protected',
        'phone','website','email','fax','startdate','enddate',
        'employees','industrycode','industrydesc','companytype',
        'companydesc','owners','financial_summary','error',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'owners' => 'array',
        'financial_summary' => 'array',
        'error' => 'array',
        'startdate' => 'date',
        'enddate' => 'date',
        'protected' => 'boolean',
    ];

    public function jobPostings()
    {
        return $this->hasMany(JobPosting::class, 'company_id', 'company_id');
    }
}
