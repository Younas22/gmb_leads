<?php

namespace App\Exports;

use App\Models\Package;
use App\Models\Payment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PackagePerformanceReportExport implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles
{
    public function collection()
    {
        return Package::withCount(['subscriptions' => function($query) {
            $query->where('status', 'active');
        }])->get();
    }

    public function headings(): array
    {
        return [
            'Package Name',
            'Type',
            'Billing Type',
            'Price (PKR)',
            'Active Subscribers',
            'Total Revenue (PKR)',
            'Status'
        ];
    }

    public function map($package): array
    {
        $totalRevenue = Payment::completed()
            ->whereHas('subscription', function($query) use ($package) {
                $query->where('package_id', $package->id);
            })
            ->sum('amount');

        return [
            $package->name,
            $package->package_for ?? 'user',
            $package->billing_type ?? 'monthly',
            number_format($package->price, 2),
            $package->subscriptions_count,
            number_format($totalRevenue, 2),
            $package->status
        ];
    }

    public function title(): string
    {
        return 'Package Performance';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
