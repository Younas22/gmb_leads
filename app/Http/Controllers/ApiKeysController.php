<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\UserApiKey;
use Illuminate\Support\Facades\Http;

class ApiKeysController extends Controller
{
    /**
     * Show API keys page
     */
    public function index()
    {
        $user = Auth::user();

        $apiKeys = UserApiKey::where('user_id', $user->id)
                            ->orderBy('created_at', 'desc')
                            ->get();

        // Get API key limit info for the view
        $apiLimit = $user->getFeatureLimit('api_limit');
        $canAddMore = $user->canAddApiKey();
        $remainingSlots = $user->getRemainingApiKeySlots();

        return view('user.api-keys', compact('user', 'apiKeys', 'apiLimit', 'canAddMore', 'remainingSlots'));
    }

    /**
     * Create new API key
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        // Check if user can add more API keys based on package limit
        if (!$user->canAddApiKey()) {
            $limit = $user->getFeatureLimit('api_limit');

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => "You have reached your API key limit ($limit). Please upgrade your package to add more keys.",
                    'limit_reached' => true
                ], 403);
            }

            return redirect()->route('user.api-keys')
                ->with('error', "You have reached your API key limit ($limit). Please upgrade your package to add more keys.");
        }

        $request->validate([
            'key_name' => 'required|string|max:255',
            'api_key' => 'required|string|min:30',
            'google_email' => 'required|email',
        ]);

        // Test API key before saving
        $isValid = $this->testGooglePlacesApi($request->api_key);
        
        // Get quota information from Google
        $quotas = $this->getGoogleApiQuotas($request->api_key);

        $apiKey = UserApiKey::create([
            'user_id' => $user->id,
            'api_provider' => 'google_places',
            'api_key' => $request->api_key,
            'key_name' => $request->key_name,
            'google_email' => $request->google_email,
            'is_active' => true,
            'is_valid' => $isValid['success'],
            'usage_count' => 0,
            'daily_limit' => $quotas['daily_limit'],
            'monthly_limit' => $quotas['monthly_limit'],
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'API key added successfully',
                'is_valid' => $isValid
            ]);
        }

        return redirect()->route('user.api-keys')->with('success', 'API key added successfully');
    }

    /**
     * Update API key
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();

        $apiKey = UserApiKey::where('user_id', $user->id)->findOrFail($id);

        // Block editing if API key is already verified
        if ($apiKey->is_valid) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Verified API keys cannot be edited for security reasons.',
                    'is_locked' => true
                ], 403);
            }

            return redirect()->route('user.api-keys')
                ->with('error', 'Verified API keys cannot be edited for security reasons.');
        }

        $request->validate([
            'key_name' => 'required|string|max:255',
            'api_key' => 'required|string|min:30',
            'google_email' => 'required|email',
        ]);

        // Test API key before updating
        $isValid = $this->testGooglePlacesApi($request->api_key);

        $apiKey->update([
            'api_key' => $request->api_key,
            'key_name' => $request->key_name,
            'google_email' => $request->google_email,
            'is_valid' => $isValid,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'API key updated successfully',
                'is_valid' => $isValid
            ]);
        }

        return redirect()->route('user.api-keys')->with('success', 'API key updated successfully');
    }

    /**
     * Toggle API key status
     */
    public function toggle(Request $request, $id)
    {
        $user = Auth::user();
        
        $apiKey = UserApiKey::where('user_id', $user->id)->findOrFail($id);
        
        $apiKey->update([
            'is_active' => !$apiKey->is_active
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'is_active' => $apiKey->is_active
            ]);
        }

        return redirect()->route('user.api-keys')->with('success', 'API key status updated');
    }



    /**
     * Delete API key
     */
    public function destroy(Request $request, $id)
    {
        $user = Auth::user();

        $apiKey = UserApiKey::where('user_id', $user->id)->findOrFail($id);

        // Block deletion if API key is verified
        if ($apiKey->is_valid) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Verified API keys cannot be deleted for security reasons.',
                    'is_locked' => true
                ], 403);
            }

            return redirect()->route('user.api-keys')
                ->with('error', 'Verified API keys cannot be deleted for security reasons.');
        }

        $apiKey->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'API key deleted successfully'
            ]);
        }

        return redirect()->route('user.api-keys')->with('success', 'API key deleted successfully');
    }

    /**
     * Test Google Places API and get quota information
     */
// Fixed Backend Controller
public function testApiKey(Request $request)
{
    try {
        $apiKey = $request->input('api_key');
        $testQuery = $request->input('query', 'restaurants in Dubai');
        
        // Validate input
        if (empty($apiKey)) {
            return response()->json([
                'success' => false,
                'message' => 'API key is required',
                'details' => 'Please enter your Google Places API key'
            ], 400);
        }
        
        // Basic format validation
        if (strlen($apiKey) < 30) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid API key format',
                'details' => 'Google Places API key should be longer than 30 characters'
            ], 400);
        }
        
        // Test the API key with actual Google Places API call
        $testResult = $this->testGooglePlacesApi($apiKey, $testQuery);
        
        if ($testResult['success']) {
            return response()->json([
                'success' => true,
                'message' => 'API key is working correctly',
                'details' => $testResult['details']
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'API key test failed',
                'details' => $testResult['error']
            ], 401);
        }
        
    } catch (\Exception $e) {
        \Log::error('API key test failed: ' . $e->getMessage());
        
        return response()->json([
            'success' => false,
            'message' => 'API validation error',
            'details' => 'An error occurred while testing the API key'
        ], 500);
    }
}

// Updated test method with better error handling
private function testGooglePlacesApi($apiKey, $query = 'restaurants in Dubai')
{
    try {
        $startTime = microtime(true);
        
        $response = Http::timeout(15)->get('https://maps.googleapis.com/maps/api/place/textsearch/json', [
            'query' => $query,
            'key' => $apiKey
        ]);
        
        $responseTime = round((microtime(true) - $startTime) * 1000);
        
        if ($response->successful()) {
            $data = $response->json();
            
            // Check API response status
            if (isset($data['status'])) {
                switch ($data['status']) {
                    case 'OK':
                        $resultsCount = count($data['results'] ?? []);
                        return [
                            'success' => true,
                            'details' => "✓ Found {$resultsCount} results • Response time: {$responseTime}ms"
                        ];
                        
                    case 'ZERO_RESULTS':
                        return [
                            'success' => true,
                            'details' => "✓ API key valid but no results found • Response time: {$responseTime}ms"
                        ];
                        
                    case 'REQUEST_DENIED':
                        return [
                            'success' => false,
                            'error' => 'API key is invalid or does not have Places API access'
                        ];
                        
                    case 'OVER_QUERY_LIMIT':
                        return [
                            'success' => false,
                            'error' => 'API key has exceeded its quota limit'
                        ];
                        
                    case 'INVALID_REQUEST':
                        return [
                            'success' => false,
                            'error' => 'Invalid request parameters'
                        ];
                        
                    default:
                        return [
                            'success' => false,
                            'error' => 'API returned status: ' . $data['status']
                        ];
                }
            }
        }
        
        // Handle HTTP errors
        $statusCode = $response->status();
        return [
            'success' => false,
            'error' => "HTTP Error {$statusCode}: " . $this->getHttpErrorMessage($statusCode)
        ];
        
    } catch (\Illuminate\Http\Client\ConnectionException $e) {
        return [
            'success' => false,
            'error' => 'Unable to connect to Google Places API'
        ];
    } catch (\Illuminate\Http\Client\RequestException $e) {
        return [
            'success' => false,
            'error' => 'Request timeout or network error'
        ];
    } catch (\Exception $e) {
        \Log::error('Google Places API test error: ' . $e->getMessage());
        return [
            'success' => false,
            'error' => 'Unexpected error occurred while testing API key'
        ];
    }
}

private function getHttpErrorMessage($statusCode)
{
    switch ($statusCode) {
        case 400:
            return 'Bad request - Check API key format';
        case 401:
            return 'Unauthorized - Invalid API key';
        case 403:
            return 'Forbidden - API key lacks permissions';
        case 429:
            return 'Too many requests - Rate limit exceeded';
        case 500:
            return 'Google server error';
        default:
            return 'Unknown error';
    }
}

// Your existing test method for saved API keys (keep this for backward compatibility)
public function test(Request $request, $id)
{
    $user = Auth::user();
    
    $apiKey = UserApiKey::where('user_id', $user->id)->findOrFail($id);
    
    $testQuery = $request->input('query', 'restaurants in Dubai');
    
    $testResult = $this->testGooglePlacesApi($apiKey->api_key, $testQuery);
    
    // Update validation status
    $apiKey->update(['is_valid' => $testResult['success']]);
    
    return response()->json([
        'success' => $testResult['success'],
        'message' => $testResult['success'] ? 'API key is working correctly' : 'API key test failed',
        'details' => $testResult['success'] ? $testResult['details'] : $testResult['error']
    ]);
}

    /**
     * Get Google Cloud quota information for API key
     */
    private function getGoogleApiQuotas($apiKey)
    {
        try {
            // Google Cloud Service Usage API call to get quota information
            // Note: This requires additional API setup in Google Cloud Console
            // For now, returning default limits as Google doesn't provide easy quota access
            
            return [
                'daily_limit' => 1000,      // Default free tier
                'monthly_limit' => 30000,   // Default free tier
                'requests_per_minute' => 100
            ];
        } catch (\Exception $e) {
            // Fallback to default limits
            return [
                'daily_limit' => 1000,
                'monthly_limit' => 30000,
                'requests_per_minute' => 100
            ];
        }
    }
}