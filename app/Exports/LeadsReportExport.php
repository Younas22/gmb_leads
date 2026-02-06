<?php

namespace App\Exports;

use App\Models\SavedLead;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LeadsReportExport implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles
{
    public function collection()
    {
        return SavedLead::with('user')
            ->latest()
            ->limit(1000)
            ->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'User Name',
            'User Email',
            'Business Name',
            'Phone',
            'Address',
            'Status',
            'Created Date'
        ];
    }

    public function map($lead): array
    {
        return [
            $lead->id,
            $lead->user ? ($lead->user->first_name ?? $lead->user->name) : 'N/A',
            $lead->user ? $lead->user->email : 'N/A',
            $lead->business_name ?? 'N/A',
            $lead->phone ?? 'N/A',
            $lead->address ?? 'N/A',
            $lead->status ?? 'new',
            $lead->created_at->format('Y-m-d H:i:s')
        ];
    }

    public function title(): string
    {
        return 'Leads Report';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
