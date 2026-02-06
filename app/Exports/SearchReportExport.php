<?php

namespace App\Exports;

use App\Models\SearchHistory;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SearchReportExport implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles
{
    public function collection()
    {
        return SearchHistory::with('user')
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
            'Query',
            'Location',
            'Results Count',
            'Status',
            'API Used',
            'Search Date'
        ];
    }

    public function map($search): array
    {
        return [
            $search->id,
            $search->user ? ($search->user->first_name ?? $search->user->name) : 'N/A',
            $search->user ? $search->user->email : 'N/A',
            $search->query,
            $search->location ?? 'N/A',
            $search->results_count ?? 0,
            $search->status ?? 'pending',
            $search->api_used ?? 'Google Places',
            $search->created_at->format('Y-m-d H:i:s')
        ];
    }

    public function title(): string
    {
        return 'Search Report';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
