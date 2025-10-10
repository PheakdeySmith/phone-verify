<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiProvider extends Model
{
    protected $fillable = [
        'name', 'base_url', 'api_key', 'api_secret', 'status', 'priority', 'default_price'
    ];

    protected $casts = [
        'default_price' => 'decimal:6'
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 'active')->orderBy('priority');
    }
}