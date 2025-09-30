<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NetworkPrefix extends Model
{
    protected $fillable = [
        "prefix",
        "min_length",
        "max_length",
        "country_name",
        "network_name",
        "mcc",
        "mnc",
        "live_coverage",
        "created_at",
        "updated_at",
    ];
}
