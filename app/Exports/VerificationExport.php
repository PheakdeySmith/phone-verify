<?php

namespace App\Exports;

use App\Models\Verification;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class VerificationExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return Verification::latest()->get();
    }

    public function headings(): array
    {
        return [
            'Phone Number',
            'Provider',
            'Cost',
            'Country',
            'Network/Carrier',
            'Status',
            'Valid',
            'Present',
            'Ported',
            'MCC',
            'MNC',
            'Prefix',
            'Fraud Score',
            'VoIP',
            'Prepaid',
            'Risky',
            'Recent Abuse',
            'Leaked Online',
            'Spammer',
            'City',
            'Region',
            'Timezone',
            'Transaction ID',
            'Verified At'
        ];
    }

    public function map($verification): array
    {
        $provider = $verification->provider;

        // Common fields
        $row = [
            $verification->phone_number,
            $provider,
            $verification->cost ? '$' . number_format($verification->cost, 6) : 'N/A',
        ];

        // Provider-specific fields
        if ($provider === 'TMT') {
            $row[] = $verification->tmt_country ?? 'N/A'; // Country
            $row[] = $verification->tmt_network ?? 'N/A'; // Network
            $row[] = $verification->tmt_status === 0 ? 'Success' : 'Failed'; // Status
            $row[] = $verification->tmt_status === 0 ? 'Yes' : 'No'; // Valid
            $row[] = $verification->tmt_present ?? 'N/A'; // Present
            $row[] = $verification->tmt_ported ? 'Yes' : 'No'; // Ported
            $row[] = $verification->tmt_mcc ?? 'N/A'; // MCC
            $row[] = $verification->tmt_mnc ?? 'N/A'; // MNC
            $row[] = $verification->tmt_prefix ?? 'N/A'; // Prefix
            $row[] = 'N/A'; // Fraud Score (not available in TMT)
            $row[] = 'N/A'; // VoIP
            $row[] = 'N/A'; // Prepaid
            $row[] = 'N/A'; // Risky
            $row[] = 'N/A'; // Recent Abuse
            $row[] = 'N/A'; // Leaked Online
            $row[] = 'N/A'; // Spammer
            $row[] = 'N/A'; // City
            $row[] = 'N/A'; // Region
            $row[] = 'N/A'; // Timezone
            $row[] = $verification->tmt_trxid ?? 'N/A'; // Transaction ID
        } elseif ($provider === 'IPQS') {
            $row[] = $verification->ipqs_country ?? 'N/A'; // Country
            $row[] = $verification->ipqs_carrier ?? 'N/A'; // Carrier
            $row[] = $verification->ipqs_valid ? 'Valid' : 'Invalid'; // Status
            $row[] = $verification->ipqs_valid ? 'Yes' : 'No'; // Valid
            $row[] = $verification->ipqs_active ? 'Yes' : 'No'; // Present
            $row[] = 'N/A'; // Ported (not available in IPQS)
            $row[] = 'N/A'; // MCC
            $row[] = 'N/A'; // MNC
            $row[] = 'N/A'; // Prefix
            $row[] = $verification->ipqs_fraud_score ?? 'N/A'; // Fraud Score
            $row[] = $verification->ipqs_voip ? 'Yes' : 'No'; // VoIP
            $row[] = $verification->ipqs_prepaid ? 'Yes' : 'No'; // Prepaid
            $row[] = $verification->ipqs_risky ? 'Yes' : 'No'; // Risky
            $row[] = $verification->ipqs_recent_abuse ? 'Yes' : 'No'; // Recent Abuse
            $row[] = $verification->ipqs_leaked_online ? 'Yes' : 'No'; // Leaked Online
            $row[] = $verification->ipqs_spammer ? 'Yes' : 'No'; // Spammer
            $row[] = $verification->ipqs_city ?? 'N/A'; // City
            $row[] = $verification->ipqs_region ?? 'N/A'; // Region
            $row[] = $verification->ipqs_timezone ?? 'N/A'; // Timezone
            $row[] = $verification->ipqs_request_id ?? 'N/A'; // Request ID
        } else {
            // Unknown provider - fill with N/A
            for ($i = 0; $i < 18; $i++) {
                $row[] = 'N/A';
            }
        }

        // Verified At (common field)
        $row[] = $verification->created_at->format('Y-m-d H:i:s');

        return $row;
    }
}