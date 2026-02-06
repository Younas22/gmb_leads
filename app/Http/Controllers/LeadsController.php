<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\SavedLead;
use App\Models\Country;
use Carbon\Carbon;
use App\Exports\LeadsExport;
use Maatwebsite\Excel\Facades\Excel;

class LeadsController extends Controller
{
    /**
     * Show saved leads
     */
public function index(Request $request)
{
    $user = Auth::user();
    
    // Get filter parameters
    $search = $request->get('search');
    $countryId = $request->get('country_id');
    $stateId = $request->get('state_id');
    $cityId = $request->get('city_id');
    $status = $request->get('status');
    $rating = $request->get('rating');
    $lastReview = $request->get('last_review');
    $reviewsCount = $request->get('reviews_count');
    $perPage = $request->get('per_page', 30);
    
    // Build query
    $query = SavedLead::where('user_id', $user->id);
    
    // Search text filter
    if ($search) {
        $query->where(function($q) use ($search) {
            $q->where('name', 'LIKE', "%{$search}%")
              ->orWhere('address', 'LIKE', "%{$search}%")
              ->orWhere('phone', 'LIKE', "%{$search}%")
              ->orWhere('email', 'LIKE', "%{$search}%")
              ->orWhere('website', 'LIKE', "%{$search}%")
              ->orWhere('search_query', 'LIKE', "%{$search}%");
        });
    }

    // Country filter (by ID)
    if ($countryId) {
        $query->where('country', $countryId);
    }

    // State filter (by ID)
    if ($stateId) {
        $query->where('state', $stateId);
    }

    // City filter (by ID)
    if ($cityId) {
        $query->where('city', $cityId);
    }
    
    // Status filter
    if ($status) {
        $query->where('contact_status', $status);
    }
    
    // Rating filter
    if ($rating) {
        $query->where('rating', '<=', $rating);
    }
    
    // Last Review filter
    if ($lastReview) {
        $cutoffDate = match($lastReview) {
            '1-day' => Carbon::now()->subDay(),
            '1-week' => Carbon::now()->subWeek(),
            '1-month' => Carbon::now()->subMonth(),
            '3-months' => Carbon::now()->subMonths(3),
            '6-months' => Carbon::now()->subMonths(6),
            default => null
        };

        if ($cutoffDate) {
            $query->where('last_review_date', '>=', $cutoffDate->toDateTimeString());
        }
    }

    // Reviews count filter
    if ($reviewsCount) {
        if ($reviewsCount === 'lt30') {
            $query->where('total_reviews', '<', 30);
        } elseif ($reviewsCount === 'lt50') {
            $query->where('total_reviews', '<', 50);
        } elseif ($reviewsCount === 'lt100') {
            $query->where('total_reviews', '<', 100);
        } elseif ($reviewsCount === 'gte100') {
            $query->where('total_reviews', '>=', 100);
        }
    }

    // Get leads with pagination
    $leads = $query->orderBy('created_at', 'desc')
                  ->paginate($perPage)
                  ->withQueryString();
    
    // Stats
    $stats = $this->getLeadStats($user->id);
    
    // Countries for dropdown
    $countries = Country::orderBy('name')->get();

    return view('user.leads', compact(
        'countries', 'user', 'leads', 'stats',
        'search', 'countryId', 'stateId', 'cityId',
        'status', 'rating', 'lastReview', 'reviewsCount'
    ));
    
}

    
    /**
     * Get lead statistics
     */
    private function getLeadStats($userId)
    {
        $total = SavedLead::where('user_id', $userId)->count();
        $contacted = SavedLead::where('user_id', $userId)->where('contact_status', 'contacted')->count();
        $pending = SavedLead::where('user_id', $userId)->where('contact_status', 'not_contacted')->count();
        $converted = SavedLead::where('user_id', $userId)->where('contact_status', 'converted')->count();
        
        return [
            'total' => $total,
            'contacted' => $contacted,
            'pending' => $pending,
            'converted' => $converted
        ];
    }
    
    /**
     * Get lead details
     */
    public function show($id)
    {
        $user = Auth::user();
        $lead = SavedLead::where('user_id', $user->id)->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'lead' => $this->formatLeadForResponse($lead)
        ]);
    }
    
    /**
     * Update lead status
     */
    public function updateStatus(Request $request, $id)
    {
        $user = Auth::user();
        $lead = SavedLead::where('user_id', $user->id)->findOrFail($id);
        
        $request->validate([
            'status' => 'required|in:not_contacted,contacted,responded,converted,not_interested'
        ]);
        
        $lead->update([
            'contact_status' => $request->status,
            'is_contacted' => $request->status !== 'not_contacted'
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Lead status updated successfully'
        ]);
    }
    
    /**
     * Update lead notes
     */
    public function updateNotes(Request $request, $id)
    {
        $user = Auth::user();
        $lead = SavedLead::where('user_id', $user->id)->findOrFail($id);
        
        $request->validate([
            'notes' => 'nullable|string|max:1000'
        ]);
        
        $lead->update(['notes' => $request->notes]);
        
        return response()->json([
            'success' => true,
            'message' => 'Notes updated successfully'
        ]);
    }
    
    /**
     * Delete lead
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $lead = SavedLead::where('user_id', $user->id)->findOrFail($id);
        
        $lead->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Lead deleted successfully'
        ]);
    }
    
    /**
     * Bulk actions
     */
    public function bulkAction(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'action' => 'required|in:delete,update_status',
            'lead_ids' => 'required|array',
            'lead_ids.*' => 'integer|exists:saved_leads,id',
            'status' => 'required_if:action,update_status|in:not_contacted,contacted,responded,converted,not_interested'
        ]);
        
        $leadIds = $request->lead_ids;
        $action = $request->action;
        
        // Verify all leads belong to the user
        $leads = SavedLead::where('user_id', $user->id)->whereIn('id', $leadIds)->get();
        
        if ($leads->count() !== count($leadIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Some leads do not belong to you'
            ], 403);
        }
        
        if ($action === 'delete') {
            SavedLead::where('user_id', $user->id)->whereIn('id', $leadIds)->delete();
            $message = 'Selected leads deleted successfully';
        } elseif ($action === 'update_status') {
            SavedLead::where('user_id', $user->id)
                    ->whereIn('id', $leadIds)
                    ->update([
                        'contact_status' => $request->status,
                        'is_contacted' => $request->status !== 'not_contacted'
                    ]);
            $message = 'Lead statuses updated successfully';
        }
        
        return response()->json([
            'success' => true,
            'message' => $message
        ]);
    }
    
    /**
     * Get latest review date from reviews sample
     */
    private function getLatestReviewDate($reviewsSample)
    {
        if (!$reviewsSample || !is_array($reviewsSample) || empty($reviewsSample)) {
            return null;
        }
        
        $latestTime = 0;
        foreach ($reviewsSample as $review) {
            if (isset($review['time']) && $review['time'] > $latestTime) {
                $latestTime = $review['time'];
            }
        }
        
        return $latestTime > 0 ? Carbon::createFromTimestamp($latestTime) : null;
    }
    
    /**
     * Export leads to CSV/Excel
     */
    public function export(Request $request)
    {
        $user = Auth::user();

        // Check export limit based on package
        $exportLimit = $user->getFeatureLimit('export_leads');

        if ($exportLimit !== -1) {
            // Count today's exports
            $todayExportCount = \App\Models\ExportHistory::where('user_id', $user->id)
                ->whereDate('created_at', today())
                ->count();

            if ($todayExportCount >= $exportLimit) {
                return back()->with('error', "You have reached your daily export limit ($exportLimit exports). Please upgrade your package or try again tomorrow.");
            }
        }

        // Get the same filtered query as the index page
        $search = $request->get('search');
        $countryId = $request->get('country_id');
        $stateId = $request->get('state_id');
        $cityId = $request->get('city_id');
        $status = $request->get('status');
        $rating = $request->get('rating');
        $lastReview = $request->get('last_review');
        $reviewsCount = $request->get('reviews_count');

        // Build query with same filters
        $query = SavedLead::where('user_id', $user->id);

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('address', 'LIKE', "%{$search}%")
                  ->orWhere('phone', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('website', 'LIKE', "%{$search}%")
                  ->orWhere('search_query', 'LIKE', "%{$search}%");
            });
        }

        if ($countryId) {
            $query->where('country', $countryId);
        }

        if ($stateId) {
            $query->where('state', $stateId);
        }

        if ($cityId) {
            $query->where('city', $cityId);
        }

        if ($status) {
            $query->where('contact_status', $status);
        }

        if ($rating) {
            $query->where('rating', '<=', $rating);
        }

        if ($lastReview) {
            $cutoffDate = match($lastReview) {
                '1-day' => Carbon::now()->subDay(),
                '1-week' => Carbon::now()->subWeek(),
                '1-month' => Carbon::now()->subMonth(),
                '3-months' => Carbon::now()->subMonths(3),
                '6-months' => Carbon::now()->subMonths(6),
                default => null
            };

            if ($cutoffDate) {
                $query->where('last_review_date', '>=', $cutoffDate->toDateTimeString());
            }
        }

        if ($reviewsCount) {
            if ($reviewsCount === 'lt30') {
                $query->where('total_reviews', '<', 30);
            } elseif ($reviewsCount === 'lt50') {
                $query->where('total_reviews', '<', 50);
            } elseif ($reviewsCount === 'lt100') {
                $query->where('total_reviews', '<', 100);
            } elseif ($reviewsCount === 'gte100') {
                $query->where('total_reviews', '>=', 100);
            }
        }

        // Get all leads (not paginated for export) with relationships
        $leads = $query->with(['countryRelation', 'stateRelation', 'cityRelation'])
                      ->orderBy('created_at', 'desc')
                      ->get();

        if ($leads->isEmpty()) {
            return back()->with('error', 'No leads to export.');
        }

        // Log export action
        \App\Models\ExportHistory::create([
            'user_id' => $user->id,
            'export_type' => 'leads',
            'records_count' => $leads->count(),
            'filters' => json_encode($request->all())
        ]);

        // Generate filename
        $filename = 'leads_export_' . date('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        return response()->stream(function() use ($leads) {
            $file = fopen('php://output', 'w');

            // CSV Headers
            fputcsv($file, [
                'Query',
                'Company',
                'Number',
                'Email',
                'Website',
                'City',
                'State',
                'Country',
                'Total Reviews',
                'Latest Review',
                'GMB Profile',
                'Facebook',
                'Instagram',
                'Twitter',
                'LinkedIn',
                'YouTube',
                'Pinterest'
            ]);

            // CSV Data
            foreach ($leads as $lead) {
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

                fputcsv($file, [
                    $lead->search_query ?? '',
                    $lead->name ?? '',
                    $lead->phone ?? '',
                    $lead->email ?? '',
                    $lead->website ?? '',
                    $lead->cityRelation?->name ?? '',
                    $lead->stateRelation?->name ?? '',
                    $lead->countryRelation?->name ?? '',
                    $lead->total_reviews ?? 0,
                    $latestReview,
                    $lead->google_profile_url ?? '',
                    $facebook,
                    $instagram,
                    $twitter,
                    $linkedin,
                    $youtube,
                    $pinterest
                ]);
            }

            fclose($file);
        }, 200, $headers);
    }

    /**
     * Export leads to Excel
     */
    public function exportExcel(Request $request)
    {
        $user = Auth::user();

        // Check export limit based on package
        $exportLimit = $user->getFeatureLimit('export_leads');

        if ($exportLimit !== -1) {
            // Count today's exports
            $todayExportCount = \App\Models\ExportHistory::where('user_id', $user->id)
                ->whereDate('created_at', today())
                ->count();

            if ($todayExportCount >= $exportLimit) {
                return back()->with('error', "You have reached your daily export limit ($exportLimit exports). Please upgrade your package or try again tomorrow.");
            }
        }

        // Get the same filtered query as the index page
        $search = $request->get('search');
        $countryId = $request->get('country_id');
        $stateId = $request->get('state_id');
        $cityId = $request->get('city_id');
        $status = $request->get('status');
        $rating = $request->get('rating');
        $lastReview = $request->get('last_review');
        $reviewsCount = $request->get('reviews_count');

        // Build query with same filters
        $query = SavedLead::where('user_id', $user->id);

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('address', 'LIKE', "%{$search}%")
                  ->orWhere('phone', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('website', 'LIKE', "%{$search}%")
                  ->orWhere('search_query', 'LIKE', "%{$search}%");
            });
        }

        if ($countryId) {
            $query->where('country', $countryId);
        }

        if ($stateId) {
            $query->where('state', $stateId);
        }

        if ($cityId) {
            $query->where('city', $cityId);
        }

        if ($status) {
            $query->where('contact_status', $status);
        }

        if ($rating) {
            $query->where('rating', '<=', $rating);
        }

        if ($lastReview) {
            $cutoffDate = match($lastReview) {
                '1-day' => Carbon::now()->subDay(),
                '1-week' => Carbon::now()->subWeek(),
                '1-month' => Carbon::now()->subMonth(),
                '3-months' => Carbon::now()->subMonths(3),
                '6-months' => Carbon::now()->subMonths(6),
                default => null
            };

            if ($cutoffDate) {
                $query->where('last_review_date', '>=', $cutoffDate->toDateTimeString());
            }
        }

        if ($reviewsCount) {
            if ($reviewsCount === 'lt30') {
                $query->where('total_reviews', '<', 30);
            } elseif ($reviewsCount === 'lt50') {
                $query->where('total_reviews', '<', 50);
            } elseif ($reviewsCount === 'lt100') {
                $query->where('total_reviews', '<', 100);
            } elseif ($reviewsCount === 'gte100') {
                $query->where('total_reviews', '>=', 100);
            }
        }

        // Get all leads (not paginated for export) with relationships
        $leads = $query->with(['countryRelation', 'stateRelation', 'cityRelation'])
                      ->orderBy('created_at', 'desc')
                      ->get();

        if ($leads->isEmpty()) {
            return back()->with('error', 'No leads to export.');
        }

        // Log export action
        \App\Models\ExportHistory::create([
            'user_id' => $user->id,
            'export_type' => 'leads',
            'records_count' => $leads->count(),
            'filters' => json_encode($request->all())
        ]);

        // Generate filename
        $filename = 'leads_export_' . date('Y-m-d_H-i-s') . '.xlsx';

        // Download Excel file
        return Excel::download(new LeadsExport($leads), $filename);
    }

    /**
     * Format lead for API response
     */
    private function formatLeadForResponse($lead)
    {
        // Decode JSON fields
        $openingHours = $lead->opening_hours ? json_decode($lead->opening_hours, true) : [];
        $socialLinks = $lead->social_links ? json_decode($lead->social_links, true) : [];
        $reviewsSample = $lead->reviews_sample ? json_decode($lead->reviews_sample, true) : [];

        // Get latest review date
        $latestReviewDate = $this->getLatestReviewDate($reviewsSample);

        return [
            'id' => $lead->id,
            'name' => $lead->name,
            'category' => $lead->category,
            'phone' => $lead->phone,
            'email' => $lead->email,
            'website' => $lead->website,
            'address' => $lead->address,
            'city' => $lead->city,
            'state' => $lead->state,
            'country' => $lead->country,
            'rating' => $lead->rating,
            'total_reviews' => $lead->total_reviews,
            'opening_hours' => $openingHours,
            'social_links' => $socialLinks,
            'reviews_sample' => $reviewsSample,
            'latest_review_date' => $latestReviewDate ? $latestReviewDate->format('M d, Y') : null,
            'latest_review_relative' => $latestReviewDate ? $latestReviewDate->diffForHumans() : null,
            'status' => $lead->contact_status,
            'notes' => $lead->notes,
            'added_date' => $lead->created_at->format('M d, Y'),
            'google_profile_url' => $lead->google_profile_url,
            'is_contacted' => $lead->is_contacted,
            'tags' => $lead->tags ? explode(',', $lead->tags) : []
        ];
    }
}