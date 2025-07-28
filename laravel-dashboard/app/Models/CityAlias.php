<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CityAlias extends Model
{
    public $timestamps = false;
    public $incrementing = false;
    protected $keyType = 'string';
    protected $primaryKey = 'alias';
    
    protected $fillable = [
        'alias',
        'city_norm'
    ];
}
