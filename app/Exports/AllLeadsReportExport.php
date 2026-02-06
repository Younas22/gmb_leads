<?php

namespace App\Exports;

use App\Models\SavedLead;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class AllLeadsReportExport implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles, WithColumnWidths
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = SavedLead::with(['user', 'cityRelation', 'stateRelation', 'countryRelation']);

        // Apply same filters as the view
        if (!empty($this->filters['user_id'])) {
            $query->where('user_id', $this->filters['user_id']);
        }

        if (!empty($this->filters['start_date'])) {
            $query->whereDate('created_at', '>=', $this->filters['start_date']);
        }

        if (!empty($this->filters['end_date'])) {
            $query->whereDate('created_at', '<=', $this->filters['end_date']);
        }

        if (!empty($this->filters['month'])) {
            $date = Carbon::parse($this->filters['month']);
            $query->whereMonth('created_at', $date->month)
                  ->whereYear('created_at', $date->year);
        }

        if (!empty($this->filters['category'])) {
            $query->where('category', 'like', '%' . $this->filters['category'] . '%');
        }

        if (!empty($this->filters['country_id'])) {
            $query->where('country', $this->filters['country_id']);
        }

        if (!empty($this->filters['state_id'])) {
            $query->where('state', $this->filters['state_id']);
        }

        if (!empty($this->filters['city_id'])) {
            $query->where('city', $this->filters['city_id']);
        }

        if (!empty($this->filters['contact_status'])) {
            $query->where('contact_status', $this->filters['contact_status']);
        }

        if (!empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('phone', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%')
                  ->orWhere('address', 'like', '%' . $search . '%');
            });
        }

        return $query->orderByDesc('created_at')->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'User Name',
            'User Email',
            'Lead Name',
            'Phone',
            'Email',
            'Address',
            'City',
            'State',
            'Country',
            'Category',
            'Rating',
            'Total Reviews',
            'Contact Status',
            'Website',
            'Date Saved',
            'Notes'
        ];
    }

    public function map($lead): array
    {
        $statusLabels = [
            'not_contacted' => 'Not Contacted',
            'contacted' => 'Contacted',
            'responded' => 'Responded',
            'converted' => 'Converted',
            'closed' => 'Closed'
        ];

        return [
            $lead->id,
            $lead->user ? ($lead->user->first_name ? $lead->user->first_name . ' ' . $lead->user->last_name : $lead->user->name) : 'N/A',
            $lead->user ? $lead->user->email : 'N/A',
            $lead->name ?? 'N/A',
            $lead->phone ?? 'N/A',
            $lead->email ?? 'N/A',
            $lead->address ?? 'N/A',
            $lead->cityRelation ? $lead->cityRelation->name : 'N/A',
            $lead->stateRelation ? $lead->stateRelation->name : 'N/A',
            $lead->countryRelation ? $lead->countryRelation->name : 'N/A',
            $lead->category ?? 'N/A',
            $lead->rating ? number_format($lead->rating, 1) : 'N/A',
            $lead->total_reviews ? number_format($lead->total_reviews) : '0',
            $statusLabels[$lead->contact_status] ?? 'N/A',
            $lead->website ?? 'N/A',
            $lead->created_at->format('Y-m-d H:i:s'),
            $lead->notes ?? ''
        ];
    }

    public function title(): string
    {
        return 'All Leads Report';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '2563EB']
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true]
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,   // ID
            'B' => 20,  // User Name
            'C' => 25,  // User Email
            'D' => 30,  // Lead Name
            'E' => 15,  // Phone
            'F' => 25,  // Email
            'G' => 40,  // Address
            'H' => 15,  // City
            'I' => 15,  // State
            'J' => 15,  // Country
            'K' => 20,  // Category
            'L' => 10,  // Rating
            'M' => 12,  // Total Reviews
            'N' => 15,  // Contact Status
            'O' => 30,  // Website
            'P' => 18,  // Date Saved
            'Q' => 30,  // Notes
        ];
    }
}
