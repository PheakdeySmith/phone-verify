<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VerificationResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'phone_number',
        'current_network_name',
        'current_network_mcc',
        'current_network_mnc',
        'current_network_spid',
        'origin_network_name',
        'origin_network_mcc',
        'origin_network_mnc',
        'origin_network_spid',
        'status',
        'status_message',
        'type',
        'ported',
    ];

    protected $casts = [
        'ported' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
}