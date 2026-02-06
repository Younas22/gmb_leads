<?php

namespace App\Exports;

use App\Models\Payment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\DB;

class RevenueReportExport implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles
{
    public function collection()
    {
        return Payment::completed()
            ->select(
                DB::raw('YEAR(created_at) as year'),
                DB::raw('MONTH(created_at) as month'),
                DB::raw('SUM(amount) as revenue'),
                DB::raw('COUNT(*) as payments_count'),
                DB::raw('AVG(amount) as avg_payment')
            )
            ->groupBy('year', 'month')
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Month',
            'Year',
            'Total Revenue (PKR)',
            'Number of Payments',
            'Average Payment (PKR)'
        ];
    }

    public function map($payment): array
    {
        return [
            date('F', mktime(0, 0, 0, $payment->month, 1)),
            $payment->year,
            number_format($payment->revenue, 2),
            $payment->payments_count,
            number_format($payment->avg_payment, 2)
        ];
    }

    public function title(): string
    {
        return 'Revenue Report';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
