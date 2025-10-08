<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiProvider extends Model
{
    protected $fillable = [
        'name', 'base_url', 'api_key', 'status'
    ];

}