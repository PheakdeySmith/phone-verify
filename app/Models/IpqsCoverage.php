<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IpqsCoverage extends Model
{
    protected $table = 'ipqs_coverage';
    
    protected $fillable = [
        'country', 'operator_id', 'carrier_name', 'cc', 
        'number_prefix', 'support_provider', 'price'
    ];

    protected $casts = [
        'support_provider' => 'boolean',
        'price' => 'decimal:6'
    ];
}