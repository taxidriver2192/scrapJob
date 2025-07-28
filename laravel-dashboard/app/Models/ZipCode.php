<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ZipCode extends Model
{
    public $timestamps = false;
    public $incrementing = false;
    protected $keyType = 'string';
    protected $primaryKey = 'postnr';
    
    protected $fillable = [
        'postnr',
        'city',
        'city_norm',
        'lat',
        'lon',
        'weight'
    ];
}
