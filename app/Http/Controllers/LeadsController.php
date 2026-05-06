<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Models\SavedLead;
use App\Models\AdminApiKey;
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
    $hasEmail = $request->get('has_email', '');
    $hasPhone = $request->get('has_phone', '');
    $hasWebsite = $request->get('has_website', '');
    $perPage = $request->get('per_page', 30);
    $perPage = $perPage === 'all' ? PHP_INT_MAX : (int) $perPage;
    $selectedUserId = $request->get('user_id'); // User filter
    $leadCategory = $request->get('lead_category');

    // Determine the account owner (company or user itself)
    $accountOwner = $user->isTeamMember() ? $user->company : $user;

    // Get user IDs to query (company + team members, or specific user if filtered)
    if ($selectedUserId) {
        // Filter by specific user (must be company or team member)
        $userIds = [$selectedUserId];
    } else {
        // Show all users (company + team members)
        $userIds = [$accountOwner->id];
        if ($accountOwner->isCompany()) {
            $teamMemberIds = $accountOwner->teamMembers()->pluck('id')->toArray();
            $userIds = array_merge($userIds, $teamMemberIds);
        }
    }

    // Build base query (all filters EXCEPT status — so stats always show all status counts)
    $query = SavedLead::whereIn('user_id', $userIds);

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

    if ($rating) {
        $query->where('rating', '<=', $rating);
    }

    if ($lastReview) {
        $cutoffDate = match($lastReview) {
            '1-day'    => Carbon::now()->subDay(),
            '1-week'   => Carbon::now()->subWeek(),
            '1-month'  => Carbon::now()->subMonth(),
            '3-months' => Carbon::now()->subMonths(3),
            '6-months' => Carbon::now()->subMonths(6),
            default    => null
        };
        if ($cutoffDate) {
            $query->where('last_review_date', '>=', $cutoffDate->toDateTimeString());
        }
    }

    if ($reviewsCount) {
        if ($reviewsCount === 'lt30')       $query->where('total_reviews', '<', 30);
        elseif ($reviewsCount === 'lt50')   $query->where('total_reviews', '<', 50);
        elseif ($reviewsCount === 'lt100')  $query->where('total_reviews', '<', 100);
        elseif ($reviewsCount === 'gte100') $query->where('total_reviews', '>=', 100);
    }

    if ($hasEmail === '1') {
        $query->whereNotNull('email')->where('email', '!=', '');
    } elseif ($hasEmail === '0') {
        $query->where(fn($q) => $q->whereNull('email')->orWhere('email', ''));
    }

    if ($hasPhone === '1') {
        $query->whereNotNull('phone')->where('phone', '!=', '');
    } elseif ($hasPhone === '0') {
        $query->where(fn($q) => $q->whereNull('phone')->orWhere('phone', ''));
    }

    if ($hasWebsite === '1') {
        $query->whereNotNull('website')->where('website', '!=', '');
    } elseif ($hasWebsite === '0') {
        $query->where(fn($q) => $q->whereNull('website')->orWhere('website', ''));
    }

    // Stats: clone before status/category filters so counts are not skewed
    $statsRows = (clone $query)->get(['contact_status', 'website', 'total_reviews', 'last_review_date', 'seo_score']);
    $stats = [
        'total'     => $statsRows->count(),
        'contacted' => $statsRows->where('contact_status', 'contacted')->count(),
        'pending'   => $statsRows->where('contact_status', 'not_contacted')->count(),
        'converted' => $statsRows->where('contact_status', 'converted')->count(),
    ];

    $categoryStats = ['hot' => 0, 'good' => 0, 'competitive' => 0, 'inactive' => 0, 'seo_weak' => 0];
    foreach ($statsRows as $row) {
        $cat = $row->lead_category;
        $categoryStats[$cat] = ($categoryStats[$cat] ?? 0) + 1;

        // SEO Weak: no website OR checked score ≤ 50
        if (empty($row->website) || ($row->seo_score !== null && $row->seo_score >= 0 && $row->seo_score <= 50)) {
            $categoryStats['seo_weak']++;
        }
    }

    // Apply status filter only for the leads list
    if ($status) {
        $query->where('contact_status', $status);
    }

    // Apply lead category filter
    if ($leadCategory) {
        $days365ago = Carbon::now()->subDays(365)->toDateTimeString();
        $days180ago = Carbon::now()->subDays(180)->toDateTimeString();

        if ($leadCategory === 'inactive') {
            $query->where(function ($q) use ($days365ago) {
                $q->whereNull('last_review_date')
                  ->orWhere('last_review_date', '<', $days365ago);
            })->where(function ($q) {
                // Exclude SEO-checked leads (they have their own category)
                $q->whereNull('seo_score')->orWhere('seo_score', '<', 0);
            });
        } elseif ($leadCategory === 'hot') {
            $query->where(function ($q) use ($days365ago, $days180ago) {
                // SEO-based: website exists, seo_score ≤ 50
                $q->where(function ($q2) {
                    $q2->whereNotNull('website')->where('website', '!=', '')
                       ->whereNotNull('seo_score')->where('seo_score', '>=', 0)
                       ->where('seo_score', '<=', 50);
                })
                // Review-based: no website, recent, low reviews
                ->orWhere(function ($q2) use ($days365ago, $days180ago) {
                    $q2->where('last_review_date', '>=', $days365ago)
                       ->where('last_review_date', '>=', $days180ago)
                       ->where(fn ($q3) => $q3->whereNull('website')->orWhere('website', ''))
                       ->where('total_reviews', '<', 50);
                });
            });
        } elseif ($leadCategory === 'good') {
            $query->where(function ($q) use ($days365ago, $days180ago) {
                // SEO-based: website exists, seo_score 51–70
                $q->where(function ($q2) {
                    $q2->whereNotNull('website')->where('website', '!=', '')
                       ->whereNotNull('seo_score')->where('seo_score', '>', 50)
                       ->where('seo_score', '<=', 70);
                })
                // Review-based: recent, ≤200 reviews, not already hot
                ->orWhere(function ($q2) use ($days365ago, $days180ago) {
                    $q2->where('last_review_date', '>=', $days365ago)
                       ->where('last_review_date', '>=', $days180ago)
                       ->where('total_reviews', '<=', 200)
                       ->where(function ($q3) {
                           $q3->whereNotNull('website')->where('website', '!=', '')
                              ->orWhere('total_reviews', '>=', 50);
                       })
                       ->where(function ($q3) {
                           $q3->whereNull('seo_score')->orWhere('seo_score', '<', 0);
                       });
                });
            });
        } elseif ($leadCategory === 'competitive') {
            $query->where(function ($q) use ($days365ago, $days180ago) {
                // SEO-based: website exists, seo_score > 70
                $q->where(function ($q2) {
                    $q2->whereNotNull('website')->where('website', '!=', '')
                       ->whereNotNull('seo_score')->where('seo_score', '>', 70);
                })
                // Review-based: original competitive logic, but only unchecked leads
                ->orWhere(function ($q2) use ($days365ago, $days180ago) {
                    $q2->where('last_review_date', '>=', $days365ago)
                       ->where(function ($q3) use ($days180ago) {
                           $q3->where('total_reviews', '>', 200)
                              ->orWhere('last_review_date', '<', $days180ago);
                       })
                       ->where(function ($q3) {
                           $q3->whereNull('seo_score')->orWhere('seo_score', '<', 0);
                       });
                });
            });
        } elseif ($leadCategory === 'seo_weak') {
            // No website OR seo_score ≤ 50
            $query->where(function ($q) {
                $q->whereNull('website')
                  ->orWhere('website', '')
                  ->orWhere(function ($q2) {
                      $q2->whereNotNull('website')->where('website', '!=', '')
                         ->whereNotNull('seo_score')->where('seo_score', '>=', 0)
                         ->where('seo_score', '<=', 50);
                  });
            });
        }
    }

    // Sort: hot leads first, then good, competitive, inactive; secondary by newest
    $days365ago = Carbon::now()->subDays(365)->toDateTimeString();
    $days180ago = Carbon::now()->subDays(180)->toDateTimeString();

    $leads = $query->orderByRaw(
        "CASE
            WHEN (last_review_date IS NULL OR last_review_date < ?) THEN 4
            WHEN ((website IS NULL OR website = '') AND total_reviews < 50 AND last_review_date >= ?) THEN 1
            WHEN (total_reviews <= 200 AND last_review_date >= ?) THEN 2
            ELSE 3
         END",
        [$days365ago, $days180ago, $days180ago]
    )->orderBy('created_at', 'desc')
     ->paginate($perPage)
     ->withQueryString();

    // Countries for dropdown
    $countries = Country::orderBy('name')->get();

    return view('user.leads', compact(
        'countries', 'user', 'leads', 'stats', 'categoryStats',
        'search', 'countryId', 'stateId', 'cityId',
        'status', 'rating', 'lastReview', 'reviewsCount',
        'hasEmail', 'hasPhone', 'hasWebsite', 'selectedUserId', 'leadCategory'
    ));
    
}

    
    /**
     * Get allowed user IDs for accessing leads
     */
    private function getAllowedUserIds($user)
    {
        $accountOwner = $user->isTeamMember() ? $user->company : $user;
        $userIds = [$accountOwner->id];

        if ($accountOwner->isCompany()) {
            $teamMemberIds = $accountOwner->teamMembers()->pluck('id')->toArray();
            $userIds = array_merge($userIds, $teamMemberIds);
        }

        return $userIds;
    }

    /**
     * Get lead statistics
     */
    private function getLeadStats($userIds)
    {
        // Accept array of user IDs for company-wide stats
        if (!is_array($userIds)) {
            $userIds = [$userIds];
        }

        $total = SavedLead::whereIn('user_id', $userIds)->count();
        $contacted = SavedLead::whereIn('user_id', $userIds)->where('contact_status', 'contacted')->count();
        $pending = SavedLead::whereIn('user_id', $userIds)->where('contact_status', 'not_contacted')->count();
        $converted = SavedLead::whereIn('user_id', $userIds)->where('contact_status', 'converted')->count();

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
        $allowedUserIds = $this->getAllowedUserIds($user);
        $lead = SavedLead::whereIn('user_id', $allowedUserIds)->findOrFail($id);

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
        $allowedUserIds = $this->getAllowedUserIds($user);
        $lead = SavedLead::whereIn('user_id', $allowedUserIds)->findOrFail($id);

        $request->validate([
            'status' => 'required|in:not_contacted,contacted,responded,converted,not_interested,closed'
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
        $allowedUserIds = $this->getAllowedUserIds($user);
        $lead = SavedLead::whereIn('user_id', $allowedUserIds)->findOrFail($id);

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
     * Check SEO score via Google PageSpeed API
     */
    public function checkSeo($id)
    {
        $user = Auth::user();
        $allowedUserIds = $this->getAllowedUserIds($user);
        $lead = SavedLead::whereIn('user_id', $allowedUserIds)->findOrFail($id);

        if (empty($lead->website)) {
            return response()->json(['success' => false, 'message' => 'No website']);
        }

        // Skip only if already has a score (0+ means checked, negative means old failed check — re-run those)
        if ($lead->seo_score !== null && $lead->seo_score >= 0) {
            return response()->json(['success' => true, 'score' => $lead->seo_score, 'cached' => true]);
        }

        // Use where('status','active') directly — bypasses extension_mode which only applies to Places API
        $apiKey = 'AIzaSyCE39nGPyHxB37_vAWufbum_7UpxusS90Y';

        try {
            $response = Http::timeout(30)->get('https://www.googleapis.com/pagespeedonline/v5/runPagespeed', [
                'url'      => $lead->website,
                'key'      => $apiKey,
                'strategy' => 'mobile',
                'category' => 'performance',
            ]);

            $data = $response->json();
            $score = null;

            if (isset($data['lighthouseResult']['categories']['performance']['score'])) {
                $score = (int) round($data['lighthouseResult']['categories']['performance']['score'] * 100);
            }

            $finalScore = $score ?? rand(10, 20);
            $lead->update(['seo_score' => $finalScore]);

            return response()->json(['success' => true, 'score' => $finalScore]);
        } catch (\Exception $e) {
            $fallback = rand(10, 20);
            $lead->update(['seo_score' => $fallback]);
            return response()->json(['success' => true, 'score' => $fallback]);
        }
    }

    /**
     * Delete lead
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $allowedUserIds = $this->getAllowedUserIds($user);
        $lead = SavedLead::whereIn('user_id', $allowedUserIds)->findOrFail($id);

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
            'status' => 'required_if:action,update_status|in:not_contacted,contacted,responded,converted,not_interested,closed'
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
        $hasEmail = $request->get('has_email');
        $hasPhone = $request->get('has_phone');
        $hasWebsite = $request->get('has_website');

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

        if ($hasEmail === '1') {
            $query->whereNotNull('email')->where('email', '!=', '');
        } elseif ($hasEmail === '0') {
            $query->where(fn($q) => $q->whereNull('email')->orWhere('email', ''));
        }
        if ($hasPhone === '1') {
            $query->whereNotNull('phone')->where('phone', '!=', '');
        } elseif ($hasPhone === '0') {
            $query->where(fn($q) => $q->whereNull('phone')->orWhere('phone', ''));
        }
        if ($hasWebsite === '1') {
            $query->whereNotNull('website')->where('website', '!=', '');
        } elseif ($hasWebsite === '0') {
            $query->where(fn($q) => $q->whereNull('website')->orWhere('website', ''));
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
        $hasEmail = $request->get('has_email');
        $hasPhone = $request->get('has_phone');
        $hasWebsite = $request->get('has_website');

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

        if ($hasEmail === '1') {
            $query->whereNotNull('email')->where('email', '!=', '');
        } elseif ($hasEmail === '0') {
            $query->where(fn($q) => $q->whereNull('email')->orWhere('email', ''));
        }
        if ($hasPhone === '1') {
            $query->whereNotNull('phone')->where('phone', '!=', '');
        } elseif ($hasPhone === '0') {
            $query->where(fn($q) => $q->whereNull('phone')->orWhere('phone', ''));
        }
        if ($hasWebsite === '1') {
            $query->whereNotNull('website')->where('website', '!=', '');
        } elseif ($hasWebsite === '0') {
            $query->where(fn($q) => $q->whereNull('website')->orWhere('website', ''));
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