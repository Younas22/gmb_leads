<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\DB;

class UserGrowthReportExport implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles
{
    public function collection()
    {
        return User::where('user_type', 'user')
            ->select(
                DB::raw('YEAR(created_at) as year'),
                DB::raw('MONTH(created_at) as month'),
                DB::raw('COUNT(*) as users_count'),
                DB::raw('SUM(CASE WHEN email_verified = 1 THEN 1 ELSE 0 END) as verified_count'),
                DB::raw('SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) as active_count')
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
            'New Users',
            'Verified Users',
            'Active Users',
            'Verification Rate (%)'
        ];
    }

    public function map($user): array
    {
        $verificationRate = $user->users_count > 0
            ? round(($user->verified_count / $user->users_count) * 100, 1)
            : 0;

        return [
            date('F', mktime(0, 0, 0, $user->month, 1)),
            $user->year,
            $user->users_count,
            $user->verified_count,
            $user->active_count,
            $verificationRate . '%'
        ];
    }

    public function title(): string
    {
        return 'User Growth Report';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
