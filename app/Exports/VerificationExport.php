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
            'Number',
            'Network',
            'MCC',
            'MNC',
            'CIC',
            'Type',
            'Ported',
            'Present',
            'Status',
            'Error',
            'Transaction ID',
            'Verified At'
        ];
    }

    public function map($verification): array
    {
        return [
            $verification->number,
            $verification->network,
            $verification->mcc,
            $verification->mnc,
            $verification->cic,
            $verification->type,
            $verification->ported ? 'Yes' : 'No',
            $verification->present,
            $verification->status_text,
            $verification->error_text,
            $verification->trxid,
            $verification->created_at->format('Y-m-d H:i:s')
        ];
    }
}