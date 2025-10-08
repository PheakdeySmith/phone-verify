<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Verification extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'prefix',
        'country', // Add country field
        'cic',
        'error',
        'imsi',
        'mcc',
        'mnc',
        'network',
        'ocn',
        'ported',
        'present',
        'status',
        'status_message',
        'type',
        'trxid',
        'provider_used',
        'cost',
        'prefix_matched',
    ];

    protected $casts = [
        'ported' => 'boolean',
        'error' => 'integer',
        'status' => 'integer',
        'cost' => 'decimal:6',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function isSuccessful(): bool
    {
        return $this->status === 0;
    }

    public function getStatusTextAttribute(): string
    {
        return match($this->status) {
            0 => 'Success',
            1 => 'Invalid Number',
            2 => 'Service/Destination Not Authorized',
            3 => 'Congestion/Timeout',
            default => 'Unknown Status'
        };
    }

    public function getErrorTextAttribute(): string
    {
        return match($this->error) {
            0 => 'OK',
            1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 21, 27, 28, 31, 32, 33, 34, 35, 36 => 'Service Error',
            191, 192, 193 => 'Network Error',
            default => 'Unknown Error'
        };
    }

    public function networkPrefix()
    {
        return $this->belongsTo(NetworkPrefix::class, 'prefix', 'prefix');
    }
}
