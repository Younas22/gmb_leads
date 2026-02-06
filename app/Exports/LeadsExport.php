<?php

namespace App\Exports;

use App\Models\SavedLead;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class LeadsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    protected $leads;

    public function __construct(Collection $leads)
    {
        $this->leads = $leads;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        // Load relationships to get names instead of IDs
        return $this->leads->load(['countryRelation', 'stateRelation', 'cityRelation']);
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'Search Query',
            'Company Name',
            'Category',
            'Phone Number',
            'Email',
            'Website',
            'Address',
            'City',
            'State',
            'Country',
            'Rating',
            'Total Reviews',
            'Latest Review Date',
            'GMB Profile URL',
            'Facebook',
            'Instagram',
            'Twitter',
            'LinkedIn',
            'YouTube',
            'Pinterest',
            'Contact Status',
            'Notes',
            'Date Added'
        ];
    }

    /**
     * @param mixed $lead
     * @return array
     */
    public function map($lead): array
    {
        // Decode social links
        $socialLinks = $lead->social_links ? json_decode($lead->social_links, true) : [];

        // Extract social media links
        $facebook = '';
        $instagram = '';
        $twitter = '';
        $linkedin = '';
        $youtube = '';
        $pinterest = '';

        if (is_array($socialLinks)) {
            foreach ($socialLinks as $link) {
                if (str_contains($link, 'facebook.com')) {
                    $facebook = $link;
                } elseif (str_contains($link, 'instagram.com')) {
                    $instagram = $link;
                } elseif (str_contains($link, 'twitter.com') || str_contains($link, 'x.com')) {
                    $twitter = $link;
                } elseif (str_contains($link, 'linkedin.com')) {
                    $linkedin = $link;
                } elseif (str_contains($link, 'youtube.com')) {
                    $youtube = $link;
                } elseif (str_contains($link, 'pinterest.com')) {
                    $pinterest = $link;
                }
            }
        }

        // Get latest review date
        $reviewsSample = $lead->reviews_sample ? json_decode($lead->reviews_sample, true) : [];
        $latestReview = '';
        if (!empty($reviewsSample) && is_array($reviewsSample)) {
            $latestTime = 0;
            foreach ($reviewsSample as $review) {
                if (isset($review['time']) && $review['time'] > $latestTime) {
                    $latestTime = $review['time'];
                }
            }
            if ($latestTime > 0) {
                $latestReview = Carbon::createFromTimestamp($latestTime)->format('Y-m-d H:i:s');
            }
        }

        return [
            $lead->search_query ?? '',
            $lead->name ?? '',
            $lead->category ?? '',
            $lead->phone ?? '',
            $lead->email ?? '',
            $lead->website ?? '',
            $lead->address ?? '',
            $lead->cityRelation?->name ?? '',
            $lead->stateRelation?->name ?? '',
            $lead->countryRelation?->name ?? '',
            $lead->rating ?? '',
            $lead->total_reviews ?? 0,
            $latestReview,
            $lead->google_profile_url ?? '',
            $facebook,
            $instagram,
            $twitter,
            $linkedin,
            $youtube,
            $pinterest,
            ucfirst(str_replace('_', ' ', $lead->contact_status ?? 'not contacted')),
            $lead->notes ?? '',
            $lead->created_at ? $lead->created_at->format('Y-m-d H:i:s') : ''
        ];
    }

    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }

    /**
     * @return array
     */
    public function columnWidths(): array
    {
        return [
            'A' => 20, // Search Query
            'B' => 30, // Company Name
            'C' => 20, // Category
            'D' => 15, // Phone Number
            'E' => 25, // Email
            'F' => 30, // Website
            'G' => 40, // Address
            'H' => 15, // City
            'I' => 15, // State
            'J' => 15, // Country
            'K' => 8,  // Rating
            'L' => 12, // Total Reviews
            'M' => 20, // Latest Review Date
            'N' => 40, // GMB Profile URL
            'O' => 30, // Facebook
            'P' => 30, // Instagram
            'Q' => 30, // Twitter
            'R' => 30, // LinkedIn
            'S' => 30, // YouTube
            'T' => 30, // Pinterest
            'U' => 15, // Contact Status
            'V' => 30, // Notes
            'W' => 20, // Date Added
        ];
    }
}
