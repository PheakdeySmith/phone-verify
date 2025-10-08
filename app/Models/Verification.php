<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Verification extends Model
{
    protected $fillable = [
        'phone_number', 'provider', 'cost',
        // TMT fields
        'tmt_prefix', 'tmt_cic', 'tmt_imsi', 'tmt_mcc', 'tmt_mnc',
        'tmt_network', 'tmt_ported', 'tmt_present', 'tmt_status', 'tmt_trxid',
        // IPQS fields
        'ipqs_formatted', 'ipqs_local_format', 'ipqs_valid', 'ipqs_active',
        'ipqs_fraud_score', 'ipqs_recent_abuse', 'ipqs_voip', 'ipqs_prepaid',
        'ipqs_risky', 'ipqs_name', 'ipqs_associated_emails', 'ipqs_carrier',
        'ipqs_line_type', 'ipqs_leaked_online', 'ipqs_spammer', 'ipqs_country',
        'ipqs_city', 'ipqs_region', 'ipqs_zip_code', 'ipqs_timezone',
        'ipqs_dialing_code', 'ipqs_active_status_enhanced', 'ipqs_request_id'
    ];

    protected $casts = [
        'cost' => 'decimal:6',
        'tmt_ported' => 'boolean',
        'ipqs_valid' => 'boolean',
        'ipqs_active' => 'boolean',
        'ipqs_recent_abuse' => 'boolean',
        'ipqs_voip' => 'boolean',
        'ipqs_prepaid' => 'boolean',
        'ipqs_risky' => 'boolean',
        'ipqs_leaked_online' => 'boolean',
        'ipqs_spammer' => 'boolean'
    ];

    // Scope for TMT verifications
    public function scopeTmt($query)
    {
        return $query->where('provider', 'TMT');
    }

    // Scope for IPQS verifications
    public function scopeIpqs($query)
    {
        return $query->where('provider', 'IPQS');
    }

    // Get only relevant data based on provider
    public function getProviderData()
    {
        if ($this->provider === 'TMT') {
            return [
                'provider' => 'TMT',
                'phone_number' => $this->phone_number,
                'cost' => $this->cost,
                'prefix' => $this->tmt_prefix,
                'cic' => $this->tmt_cic,
                'imsi' => $this->tmt_imsi,
                'mcc' => $this->tmt_mcc,
                'mnc' => $this->tmt_mnc,
                'network' => $this->tmt_network,
                'ported' => $this->tmt_ported,
                'present' => $this->tmt_present,
                'status' => $this->tmt_status,
                'trxid' => $this->tmt_trxid,
            ];
        } elseif ($this->provider === 'IPQS') {
            return [
                'provider' => 'IPQS',
                'phone_number' => $this->phone_number,
                'cost' => $this->cost,
                'formatted' => $this->ipqs_formatted,
                'local_format' => $this->ipqs_local_format,
                'valid' => $this->ipqs_valid,
                'active' => $this->ipqs_active,
                'fraud_score' => $this->ipqs_fraud_score,
                'recent_abuse' => $this->ipqs_recent_abuse,
                'voip' => $this->ipqs_voip,
                'prepaid' => $this->ipqs_prepaid,
                'risky' => $this->ipqs_risky,
                'name' => $this->ipqs_name,
                'associated_emails' => $this->ipqs_associated_emails,
                'carrier' => $this->ipqs_carrier,
                'line_type' => $this->ipqs_line_type,
                'leaked_online' => $this->ipqs_leaked_online,
                'spammer' => $this->ipqs_spammer,
                'country' => $this->ipqs_country,
                'city' => $this->ipqs_city,
                'region' => $this->ipqs_region,
                'zip_code' => $this->ipqs_zip_code,
                'timezone' => $this->ipqs_timezone,
                'dialing_code' => $this->ipqs_dialing_code,
                'active_status_enhanced' => $this->ipqs_active_status_enhanced,
                'request_id' => $this->ipqs_request_id,
            ];
        }

        return [];
    }
}