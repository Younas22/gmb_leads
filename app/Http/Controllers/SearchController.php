<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Models\Country;
use App\Models\SavedLead;
use App\Models\SearchHistory;
use App\Models\State;
use App\Models\City;

class SearchController extends Controller
{
    /**
     * Show search places page
     */
    public function index()
    {
        $user = Auth::user();
        $countries = Country::orderBy('name')->get();
        
        return view('user.search', compact('user', 'countries'));
    }

    /**
     * Get states by country
     */
    public function getStates($countryId)
    {
        // dd($countryId);
        $states = State::where('country_id', $countryId)->orderBy('name')->get();
        return response()->json($states);
    }

    /**
     * Get cities by state
     */
    public function getCities($stateId)
    {
        $cities = City::where('state_id', $stateId)->orderBy('name')->get();
        return response()->json($cities);
    }

    /**
     * Search places using Google Places API
     */

    public function search(Request $request)
{
    $user = Auth::user();
    $startTime = microtime(true);

    // Check search limit based on package (only for new searches, not pagination)
    if (!$request->input('page_token')) {
        $searchLimit = $user->getFeatureLimit('gmb_searches');

        if ($searchLimit !== -1) {
            // Count today's searches
            $todaySearchCount = SearchHistory::where('user_id', $user->id)
                ->whereDate('created_at', today())
                ->count();

            if ($todaySearchCount >= $searchLimit) {
                $errorMessage = "You have reached your daily search limit ($searchLimit searches). Please <a href='" . route('user.subscription') . "' class='text-blue-600 underline'>upgrade your package</a> or try again tomorrow.";

                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'error' => $errorMessage,
                        'errors' => ['api' => [$errorMessage]]
                    ], 429);
                }

                return back()->withErrors(['api' => $errorMessage])->withInput();
            }
        }
    }

    // Validation
    $request->validate([
        'query' => 'required|string|max:255',
        'country_id' => 'required|integer|exists:countries,id',
        'state_id' => 'nullable|integer|exists:states,id',
        'city_id' => 'nullable|integer|exists:cities,id',
        'radius' => 'nullable|integer|min:1|max:100',
        'page_token' => 'nullable|string'
    ]);

    $query = $request->input('query');
    $countryId = $request->input('country_id');
    $stateId = $request->input('state_id');
    $cityId = $request->input('city_id');
    $radius = $request->input('radius', 10);
    $pageToken = $request->input('page_token');

    // Get location details with lat/long
    $country = Country::find($countryId);
    $state = $stateId ? State::find($stateId) : null;
    $city = $cityId ? City::find($cityId) : null;

    // Get coordinates
    $coordinates = $this->getCoordinates($city, $state, $country);

    if (!$coordinates) {
        $errorMessage = 'Unable to determine location coordinates';
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'error' => $errorMessage,
                'errors' => ['api' => [$errorMessage]]
            ], 400);
        }
        return back()->withErrors(['api' => $errorMessage]);
    }

    // Create search history record with PENDING status (only for new searches, not pagination)
    $searchHistory = null;
    if (!$pageToken) {
        $searchHistory = $this->createPendingSearchHistory([
            'user_id' => $user->id,
            'query' => $query,
            'location' => $this->buildLocationString($city, $state, $country),
            'latitude' => $coordinates['lat'],
            'longitude' => $coordinates['lng'],
            'radius' => $radius,
            'api_used' => 'custom_api'
        ]);
    }

    try {
        // Get user's active and valid API key dynamically
        $userApiKey = \App\Models\UserApiKey::where('user_id', $user->id)
            ->where('is_active', true)
            ->where('is_valid', true)
            ->orderBy('last_used', 'asc') // Use least recently used key for load balancing
            ->first();

        if (!$userApiKey) {
            // Update search history to FAILED
            if ($searchHistory) {
                $this->updateSearchHistoryToFailed($searchHistory, 'No active API key found');
            }
            $errorMessage = 'No active API key found. Please add and verify your Google Places API key first.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'error' => $errorMessage,
                    'errors' => ['api' => [$errorMessage]]
                ], 400);
            }
            return back()->withErrors(['api' => $errorMessage]);
        }

        $apiKey = $userApiKey->api_key;

        // Increment API key usage
        $userApiKey->incrementUsage();
        
        // testing
        // $apiUrl = 'https://49427c66-cced-4efb-b89d-60208e8abb7a-00-j1idesu3zod2.pike.replit.dev:8080/search';
        // live
        $apiUrl = 'https://api.customernearme.com/search'; // Alternative URL if needed
        $params = [
            'key' => $apiKey,
            'query' => $query,
            'location' => $coordinates['lat'] . ',' . $coordinates['lng'],
            'radius' => $radius * 1000, // Convert km to meters
        ];

        \Log::info('Search API params', $params);

        // Add page token if provided
        if ($pageToken) {
            $params['pagetoken'] = $pageToken;
        }
        
        // Make API call with retry mechanism and longer timeout
        $maxRetries = 3;
        $retryCount = 0;
        $response = null;
        $apiStartTime = microtime(true); // Track API response time
        
        while ($retryCount < $maxRetries) {
            try {
                $response = Http::timeout(90) // Increased timeout to 90 seconds
                    ->connectTimeout(30) // Connection timeout
                    ->retry(2, 1000) // Retry 2 times with 1 second delay
                    ->withOptions([
                        'verify' => false, // Skip SSL verification if needed
                        'http_errors' => false, // Don't throw exception on HTTP errors
                    ])
                    ->get($apiUrl, $params);
                
                // If we get a response, break out of retry loop
                if ($response && $response->status() !== 0) {
                    break;
                }
                
            } catch (\Exception $e) {
                $retryCount++;
                if ($retryCount >= $maxRetries) {
                    throw $e;
                }
                
                // Wait before retrying (exponential backoff)
                sleep(pow(2, $retryCount));
            }
        }

        $apiEndTime = microtime(true);
        $apiResponseTime = round($apiEndTime - $apiStartTime, 3);

        if (!$response) {
            // Update search history to FAILED
            if ($searchHistory) {
                $this->updateSearchHistoryToFailed($searchHistory, 'Failed to connect to search API');
            }
            $errorMessage = 'Failed to connect to search API. Please try again later.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'error' => $errorMessage,
                    'errors' => ['api' => [$errorMessage]]
                ], 500);
            }
            return back()->withErrors(['api' => $errorMessage]);
        }

        if ($response->successful()) {
            $endTime = microtime(true);
            $executionTime = round($endTime - $startTime, 3);
            
            $apiData = $response->json();
            
            // Extract results and next page token
            $results = $apiData['results'] ?? $apiData;
            $nextPageToken = $apiData['next_page_token'] ?? null;
            
            // Check if results is empty
            if (empty($results)) {
                // Update search history to SUCCESS but with 0 results
                if ($searchHistory) {
                    $this->updateSearchHistoryToSuccess($searchHistory, [], $executionTime, $apiResponseTime);
                }
                return back()->with('info', 'No results found for your search criteria. Try adjusting your search terms or location.');
            }
            
            // Process and format results
            $formattedResults = $this->formatApiResults($results);
            
            // Update search history to SUCCESS (only for new searches, not pagination)
            if ($searchHistory) {
                $this->updateSearchHistoryToSuccess($searchHistory, $formattedResults, $executionTime, $apiResponseTime);
            }
            
            // Get countries for form repopulation
            $countries = Country::orderBy('name')->get();
            
            return view('user.search', compact('user', 'formattedResults', 'countries'))
                ->with('searchPerformed', true)
                ->with('totalResults', count($formattedResults))
                ->with('nextPageToken', $nextPageToken)
                ->with('searchData', [
                    'query' => $query,
                    'country_id' => $countryId,
                    'state_id' => $stateId,
                    'city_id' => $cityId,
                    'radius' => $radius,
                    'location_name' => $this->buildLocationString($city, $state, $country),
                    'page_token' => $pageToken
                ]);
        } else {
            // Handle specific HTTP error codes
            $statusCode = $response->status();
            $errorMessage = match($statusCode) {
                429 => 'API rate limit exceeded. Please wait a moment and try again.',
                500, 502, 503, 504 => 'Search service is temporarily unavailable. Please try again later.',
                404 => 'Search service not found. Please contact support.',
                default => 'Search service returned an error (Status: ' . $statusCode . '). Please try again.'
            };

            // Update search history to FAILED
            if ($searchHistory) {
                $this->updateSearchHistoryToFailed($searchHistory, $errorMessage . ' (HTTP ' . $statusCode . ')');
            }

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'error' => $errorMessage,
                    'errors' => ['api' => [$errorMessage]]
                ], $statusCode);
            }

            return back()->withErrors(['api' => $errorMessage]);
        }
        
    } catch (\Illuminate\Http\Client\ConnectionException $e) {
        // Update search history to FAILED
        if ($searchHistory) {
            $this->updateSearchHistoryToFailed($searchHistory, 'Connection error: ' . $e->getMessage());
        }

        // Handle connection-specific errors
        $errorMessage = 'Unable to connect to search service. Please check your internet connection and try again.';
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'error' => $errorMessage,
                'errors' => ['api' => [$errorMessage]]
            ], 500);
        }
        return back()->withErrors(['api' => $errorMessage]);

    } catch (\Illuminate\Http\Client\RequestException $e) {
        // Handle request-specific errors
        $errorMsg = 'Request failed: ' . $e->getMessage();
        if (str_contains($e->getMessage(), 'timeout')) {
            $errorMsg = 'Search request timed out. The service might be busy.';
        }

        // Update search history to FAILED
        if ($searchHistory) {
            $this->updateSearchHistoryToFailed($searchHistory, $errorMsg);
        }

        $errorMessage = str_contains($e->getMessage(), 'timeout')
            ? 'Search request timed out. The service might be busy. Please try again in a few moments.'
            : 'Request failed: ' . $e->getMessage();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'error' => $errorMessage,
                'errors' => ['api' => [$errorMessage]]
            ], 500);
        }
        return back()->withErrors(['api' => $errorMessage]);

    } catch (\Exception $e) {
        // Log the full error for debugging
        \Log::error('Search API Error: ' . $e->getMessage(), [
            'query' => $query,
            'location' => $coordinates,
            'params' => $params
        ]);

        // Update search history to FAILED
        if ($searchHistory) {
            $this->updateSearchHistoryToFailed($searchHistory, 'Unexpected error: ' . $e->getMessage());
        }

        // Generic error message for users
        $errorMessage = 'An unexpected error occurred while searching. Please try again later.';
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'error' => $errorMessage,
                'errors' => ['api' => [$errorMessage]]
            ], 500);
        }
        return back()->withErrors(['api' => $errorMessage]);
    }
}

/**
 * Create search history with PENDING status
 */
private function createPendingSearchHistory($data)
{
    try {
        $data['status'] = 'pending';
        $data['created_at'] = now();
        
        \Log::info('Creating pending search history', $data);
        $history = SearchHistory::create($data);
        \Log::info('Pending search history created successfully', ['id' => $history->id]);
        
        return $history;
    } catch (\Exception $e) {
        \Log::error('Failed to create pending search history: ' . $e->getMessage(), [
            'data' => $data,
            'trace' => $e->getTraceAsString()
        ]);
        return null;
    }
}

/**
 * Update search history to SUCCESS status
 */
private function updateSearchHistoryToSuccess($searchHistory, $results, $executionTime, $apiResponseTime)
{
    try {
        $updateData = [
            'status' => 'success',
            'results_count' => count($results),
            'execution_time' => $executionTime,
            'response_time' => $apiResponseTime,
            'results_data' => $results, // Store the actual results
            'error_message' => null // Clear any previous error
        ];
        
        $searchHistory->update($updateData);
        
        \Log::info('Search history updated to SUCCESS', [
            'id' => $searchHistory->id,
            'results_count' => count($results),
            'execution_time' => $executionTime
        ]);
        
    } catch (\Exception $e) {
        \Log::error('Failed to update search history to success: ' . $e->getMessage(), [
            'search_history_id' => $searchHistory->id,
            'trace' => $e->getTraceAsString()
        ]);
    }
}

/**
 * Update search history to FAILED status
 */
private function updateSearchHistoryToFailed($searchHistory, $errorMessage)
{
    try {
        $updateData = [
            'status' => 'failed',
            'error_message' => $errorMessage,
            'results_count' => 0
        ];
        
        $searchHistory->update($updateData);
        
        \Log::info('Search history updated to FAILED', [
            'id' => $searchHistory->id,
            'error' => $errorMessage
        ]);
        
    } catch (\Exception $e) {
        \Log::error('Failed to update search history to failed: ' . $e->getMessage(), [
            'search_history_id' => $searchHistory->id,
            'trace' => $e->getTraceAsString()
        ]);
    }
}

/**
 * Save selected leads
 */
public function saveLeads(Request $request)
{
    $user = Auth::user();

    \Log::info('Save leads request received', [
        'user_id' => $user->id,
        'request_data' => $request->all(),
        'headers' => $request->headers->all()
    ]);

    try {
        $request->validate([
            'leads' => 'required|array|min:1',
            'leads.*' => 'required|array',
            'search_data' => 'required|array'
        ]);
    } catch (\Illuminate\Validation\ValidationException $e) {
        \Log::error('Validation failed for save leads', [
            'errors' => $e->errors()
        ]);
        throw $e;
    }

    $leads = $request->input('leads');
    $searchData = $request->input('search_data');

    // Check saved leads limit based on package
    $savedLeadsLimit = $user->getFeatureLimit('saved_lists');

    if ($savedLeadsLimit !== -1) {
        // Count current saved leads
        $currentSavedCount = SavedLead::where('user_id', $user->id)->count();
        $leadsToSave = count($leads);
        $totalAfterSave = $currentSavedCount + $leadsToSave;

        if ($currentSavedCount >= $savedLeadsLimit) {
            return response()->json([
                'success' => false,
                'message' => "You have reached your saved leads limit ($savedLeadsLimit). Please upgrade your package to save more leads."
            ], 403);
        }

        if ($totalAfterSave > $savedLeadsLimit) {
            $allowedToSave = $savedLeadsLimit - $currentSavedCount;
            return response()->json([
                'success' => false,
                'message' => "You can only save $allowedToSave more lead(s). You have $currentSavedCount/$savedLeadsLimit saved leads. Please upgrade your package."
            ], 403);
        }
    }

    $savedCount = 0;
    $duplicateCount = 0;

    try {
        foreach ($leads as $leadData) {
            $placeId = $leadData['profile'] ?? null;

            if ($placeId && strpos($placeId, 'maps.google.com') !== false) {
                preg_match('/cid=(\d+)/', $placeId, $matches);
                $placeId = $matches[1] ?? $placeId;
            }

            $exists = SavedLead::existsForUser(
                $user->id,
                $placeId,
                $leadData['name'] ?? null,
                $leadData['address'] ?? null
            );

            if ($exists) {
                $duplicateCount++;
                continue;
            }

            $socialLinks = $leadData['social_links'] ?? [];
            $email = (!empty($leadData['emails'][0])) ? $leadData['emails'][0] : null;
            $reviewsSample = isset($leadData['reviews']) ? array_slice($leadData['reviews'], 0, 3) : [];
            $locationParts = $this->parseAddress($leadData['address'] ?? '');
            

            // Find latest review date (if any)
            $lastReviewDate = null;
            if (!empty($reviewsSample)) {
                $latestTime = max(array_column($reviewsSample, 'time')); // get max UNIX timestamp
                $lastReviewDate = date('Y-m-d H:i:s', $latestTime); // convert to MySQL datetime
            }

            try {
                SavedLead::create([
                    'user_id' => $user->id,
                    'place_id' => $placeId,
                    'name' => $leadData['name'] ?? 'Unknown',
                    'address' => $leadData['address'] ?? null,
                    'phone' => $leadData['phone'] ?? null,
                    'website' => $leadData['website'] ?? null,
                    'email' => $email,
                    'latitude' => null,
                    'longitude' => null,
                    'city' => $searchData['city_id'] ?? null,
                    'state' => $searchData['state_id'] ?? null,
                    'country' => $searchData['country_id'] ?? null,
                    'postal_code' => $locationParts['postal_code'] ?? null,
                    'category' => $searchData['query'] ?? null,
                    'rating' => isset($leadData['rating']) ? (float) $leadData['rating'] : null,
                    'total_reviews' => isset($leadData['total_reviews']) ? (int) $leadData['total_reviews'] : 0,
                    'last_review_date' => $lastReviewDate, // ✅ new column
                    'opening_hours' => json_encode($leadData['opening_hours'] ?? []),
                    'google_profile_url' => $leadData['profile'] ?? null,
                    'social_links' => json_encode($socialLinks),
                    'reviews_sample' => json_encode($reviewsSample),
                    'search_query' => $searchData['query'] ?? null,
                    'search_location' => $searchData['location_name'] ?? null,
                    'search_radius' => $searchData['radius'] ?? null,
                    'found_via_api' => 'custom_api',
                    'contact_status' => 'not_contacted'
                ]);
            } catch (\Exception $inner) {
                \Log::error('Single lead save error: ' . $inner->getMessage(), [
                    'lead' => $leadData
                ]);
                continue;
            }

            $savedCount++;
        }

        $message = "Successfully saved {$savedCount} leads.";
        if ($duplicateCount > 0) {
            $message .= " {$duplicateCount} duplicate leads were skipped.";
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'saved_count' => $savedCount,
            'duplicate_count' => $duplicateCount
        ]);
    } catch (\Exception $e) {
        \Log::error('Save leads error: ' . $e->getMessage(), [
            'user_id' => $user->id,
            'leads_count' => count($leads),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'An error occurred while saving leads. Please try again.'
        ], 500);
    }
}





/**
 * Parse address to extract location components
 */
private function parseAddress($address)
{
    $parts = [
        'city' => null,
        'state' => null,
        'country' => null,
        'postal_code' => null
    ];

    if (!$address) {
        return $parts;
    }

    // Split address by commas and clean up
    $addressParts = array_map('trim', explode(',', $address));
    $addressParts = array_reverse($addressParts); // Reverse to get country first

    if (count($addressParts) >= 1) {
        $parts['country'] = $addressParts[0];
    }
    if (count($addressParts) >= 2) {
        $parts['state'] = $addressParts[1];
    }
    if (count($addressParts) >= 3) {
        $parts['city'] = $addressParts[2];
    }

    // Extract postal code using regex
    if (preg_match('/\b\d{5,6}\b/', $address, $matches)) {
        $parts['postal_code'] = $matches[0];
    }

    return $parts;
}

/**
 * Get coordinates from city, state, or country
 */
private function getCoordinates($city, $state, $country)
{
    // Priority: City > State > Country
    if ($city && $city->latitude && $city->longitude) {
        return [
            'lat' => (float) $city->latitude,
            'lng' => (float) $city->longitude
        ];
    }
    
    if ($state && $state->latitude && $state->longitude) {
        return [
            'lat' => (float) $state->latitude,
            'lng' => (float) $state->longitude
        ];
    }
    
    if ($country && $country->latitude && $country->longitude) {
        return [
            'lat' => (float) $country->latitude,
            'lng' => (float) $country->longitude
        ];
    }
    
    return null;
}

/**
 * Build location string for API call
 */
private function buildLocationString($city, $state, $country)
{
    $locationParts = [];
    
    if ($city) {
        $locationParts[] = $city->name;
    }
    
    if ($state) {
        $locationParts[] = $state->name;
    }
    
    $locationParts[] = $country->name;
    
    return implode(', ', $locationParts);
}

/**
 * Format search results from custom API
 */

private function formatApiResults($results)
{
    if (!is_array($results)) {
        return [];
    }

    return collect($results)
        ->map(function ($place) {
            return [
                'name' => $place['name'] ?? 'Unknown',
                'address' => $place['address'] ?? 'Address not available',
                'phone' => $place['phone'] ?? null,
                'website' => $place['website'] ?? null,
                'profile' => $place['profile'] ?? null,
                'rating' => isset($place['rating']) ? (float) $place['rating'] : 0,
                'total_reviews' => isset($place['total_reviews']) ? (int) $place['total_reviews'] : 0,
                'opening_hours' => $place['opening_hours'] ?? [],
                'emails' => $place['emails'] ?? [],
                'social_links' => $place['social_links'] ?? [],
                'reviews' => $place['reviews'] ?? []
            ];
        })
        ->filter(function ($place) {
            // ✅ Phone ya Emails dono missing na hon
            return !empty($place['phone']) || !empty($place['emails']);
        })
        ->values() // reindex array
        ->toArray();
}




public function history(Request $request)
{
    $user = Auth::user();
    
    // Base query
    $query = SearchHistory::where('user_id', $user->id);
    
    // Apply filters
    if ($request->filled('date_range') && $request->date_range !== 'all') {
        $days = (int) $request->date_range;
        $query->where('created_at', '>=', now()->subDays($days));
    }
    
    if ($request->filled('query')) {
        $query->where('query', 'like', '%' . $request->query . '%');
    }
    
    if ($request->filled('location')) {
        $query->where('location', 'like', '%' . $request->location . '%');
    }
    
    if ($request->filled('status')) {
        $query->where('status', $request->status);
    }
    
    // Get paginated results
    $histories = $query->orderBy('created_at', 'desc')->paginate(10);
    
    // Append query parameters to pagination links
    $histories->appends($request->query());
    
    // Calculate stats for the entire user history (not just filtered)
    $stats = SearchHistory::getUserStats($user->id);
    
    return view('user.search-history', compact('user', 'histories', 'stats'));
}

public function rerunSearch(Request $request)
{
    $request->validate([
        'query' => 'required|string|max:255',
        'location' => 'nullable|string|max:255'
    ]);
    
    // Redirect to search page with the query parameters
    return redirect()->route('user.search')->with([
        'query' => $request->query,
        'location' => $request->location,
        'message' => 'Search parameters loaded. Click search to re-run.'
    ]);
}

public function deleteSearchHistory($id)
{
    $user = Auth::user();
    $history = SearchHistory::where('user_id', $user->id)->findOrFail($id);
    
    $history->delete();
    
    return redirect()->back()->with('success', 'Search history deleted successfully.');
}

public function viewSearchResults($id)
{
    $user = Auth::user();
    $history = SearchHistory::where('user_id', $user->id)->findOrFail($id);
    
    if (!$history->results_data) {
        return redirect()->back()->with('error', 'No results data available for this search.');
    }
    
    return view('user.search-results', compact('history'));
}

public function exportSearchHistory(Request $request)
{
    $user = Auth::user();
    
    $histories = SearchHistory::where('user_id', $user->id)
        ->orderBy('created_at', 'desc')
        ->get();
    
    $filename = 'search_history_' . date('Y-m-d_H-i-s') . '.csv';
    
    $headers = [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => 'attachment; filename="' . $filename . '"',
    ];
    
    return response()->stream(function() use ($histories) {
        $file = fopen('php://output', 'w');
        
        // CSV Headers
        fputcsv($file, [
            'Query',
            'Location',
            'Status',
            'Results Count',
            'API Used',
            'Response Time (s)',
            'Search Date',
            'Error Message'
        ]);
        
        // CSV Data
        foreach ($histories as $history) {
            fputcsv($file, [
                $history->query,
                $history->location ?? '',
                $history->status,
                $history->results_count ?? 0,
                $history->api_used ?? 'Google Places',
                $history->response_time ?? '',
                $history->created_at->format('Y-m-d H:i:s'),
                $history->error_message ?? ''
            ]);
        }
        
        fclose($file);
    }, 200, $headers);
}
}