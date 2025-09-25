<?php

namespace App\Exports;

use App\Models\VerificationResult;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class VerificationResultsExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return VerificationResult::latest()->get();
    }

    public function headings(): array
    {
        return [
            'Phone Number',
            'Current Network',
            'Origin Network',
            'Status',
            'Type',
            'Ported',
            'Ported Date',
            'Roaming',
            'Verified At'
        ];
    }

    public function map($result): array
    {
        return [
            $result->phone_number,
            $result->current_network_name,
            $result->origin_network_name,
            $result->status_message,
            $result->type,
            $result->ported ? 'Yes' : 'No',
            $result->ported_date ? $result->ported_date->format('Y-m-d H:i:s') : 'N/A',
            $result->is_roaming ? 'Yes' : 'No',
            $result->created_at->format('Y-m-d H:i:s')
        ];
    }
}