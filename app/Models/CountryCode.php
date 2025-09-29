<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CountryCode extends Model
{
    protected $fillable = [
        'country_name',
        'dial_code',
        'iso2'
    ];

    public function carrierPrefixes()
    {
        return $this->hasMany(CarrierPrefixMapping::class, 'country_code', 'dial_code');
    }
}
