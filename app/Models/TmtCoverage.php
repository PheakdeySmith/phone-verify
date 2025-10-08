<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TmtCoverage extends Model
{
    protected $table = 'tmt_coverage';
    
    protected $fillable = [
        'iso2', 'network_id', 'network_name', 'mcc', 'mnc', 
        'country_code', 'prefix', 'live_coverage', 'rate'
    ];

    protected $casts = [
        'live_coverage' => 'boolean',
        'rate' => 'decimal:6'
    ];
}