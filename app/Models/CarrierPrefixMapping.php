<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CarrierPrefixMapping extends Model
{
    protected $fillable = [
        'country_code',
        'iso2',
        'prefix',
        'carrier_keyword'
    ];

    public function countryCode()
    {
        return $this->belongsTo(CountryCode::class, 'country_code', 'dial_code');
    }
}
