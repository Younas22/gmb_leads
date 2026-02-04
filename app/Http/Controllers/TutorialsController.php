<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\UserTutorialProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TutorialsController extends Controller
{
    /**
     * Show the tutorials page
     */
    public function index()
    {
        $user = Auth::user();
        
        // Get user's completed tutorials
        $completedTutorials = UserTutorialProgress::where('user_id', $user->id)
            ->where('completed', true)
            ->pluck('tutorial_key')
            ->toArray();

        // Define tutorial structure
        $tutorials = $this->getTutorialData();
        
        // Calculate progress
        $completedCount = count($completedTutorials);
        $totalTutorials = count($tutorials);
        $progressPercentage = $totalTutorials > 0 ? round(($completedCount / $totalTutorials) * 100) : 0;
        
        // Calculate total duration
        $totalDuration = array_sum(array_map(function($tutorial) {
            return (int) filter_var($tutorial['duration'], FILTER_SANITIZE_NUMBER_INT);
        }, $tutorials));

        // Prepare tutorials data for JavaScript
        $tutorialsData = [];
        foreach ($tutorials as $tutorial) {
            $tutorialsData[$tutorial['key']] = [
                'title' => $tutorial['title'],
                'description' => $tutorial['description'],
                'duration' => $tutorial['duration'],
                'objectives' => $tutorial['objectives']
            ];
        }

        return view('user.tutorials', compact(
            'tutorials',
            'completedTutorials',
            'completedCount',
            'progressPercentage',
            'totalDuration',
            'tutorialsData'
        ));
    }

    /**
     * Mark tutorial as completed
     */
    public function markCompleted(Request $request)
    {
        $request->validate([
            'tutorial_key' => 'required|string'
        ]);

        $user = Auth::user();
        $tutorialKey = $request->tutorial_key;

        // Check if tutorial exists
        $tutorials = $this->getTutorialData();
        $tutorialExists = collect($tutorials)->contains('key', $tutorialKey);

        if (!$tutorialExists) {
            return response()->json(['success' => false, 'message' => 'Invalid tutorial']);
        }

        // Mark as completed (or update existing record)
        UserTutorialProgress::updateOrCreate(
            [
                'user_id' => $user->id,
                'tutorial_key' => $tutorialKey
            ],
            [
                'completed' => true,
                'completed_at' => now()
            ]
        );

        return response()->json(['success' => true]);
    }

    /**
     * Reset all tutorial progress
     */
    public function resetProgress(Request $request)
    {
        $user = Auth::user();
        
        UserTutorialProgress::where('user_id', $user->id)->delete();
        
        return response()->json(['success' => true]);
    }

    /**
     * Get tutorial data structure
     */
    private function getTutorialData()
    {
        return [
            [
                'key' => 'dashboard',
                'title' => 'Dashboard Overview',
                'description' => 'Learn to navigate your dashboard and understand key metrics',
                'duration' => '6 min',
                'category' => 'getting-started',
                'order' => 1,
                'icon' => 'home',
                'color' => 'blue',
                'youtube_id' => null, // Add YouTube video ID when available
                'objectives' => [
                    'Navigate the main dashboard interface',
                    'Understand key performance metrics',
                    'Use quick search functionality',
                    'Access recent activity and notifications'
                ]
            ],
            [
                'key' => 'search',
                'title' => 'Search Places',
                'description' => 'Master the search functionality and advanced filters',
                'duration' => '8 min',
                'category' => 'getting-started',
                'order' => 2,
                'icon' => 'search',
                'color' => 'green',
                'youtube_id' => null,
                'objectives' => [
                    'Perform basic and advanced searches',
                    'Use location-based filtering',
                    'Apply category and rating filters',
                    'Export search results'
                ]
            ],
            [
                'key' => 'leads',
                'title' => 'Managing Saved Leads',
                'description' => 'Organize, export, and manage your lead collection',
                'duration' => '7 min',
                'category' => 'getting-started',
                'order' => 3,
                'icon' => 'bookmark',
                'color' => 'orange',
                'youtube_id' => null,
                'objectives' => [
                    'Save and organize leads effectively',
                    'Add notes and tags to leads',
                    'Export leads to CSV format',
                    'Track contact status and follow-ups'
                ]
            ],
            [
                'key' => 'api-keys',
                'title' => 'API Keys Setup',
                'description' => 'Configure Google Places API and other integrations',
                'duration' => '10 min',
                'category' => 'advanced',
                'order' => 4,
                'icon' => 'key',
                'color' => 'purple',
                'youtube_id' => null,
                'objectives' => [
                    'Obtain Google Places API key',
                    'Configure API settings in the system',
                    'Test API connectivity',
                    'Troubleshoot common API issues'
                ]
            ],
            [
                'key' => 'history',
                'title' => 'Search History Analysis',
                'description' => 'Track performance and optimize your search strategies',
                'duration' => '6 min',
                'category' => 'advanced',
                'order' => 5,
                'icon' => 'history',
                'color' => 'indigo',
                'youtube_id' => null,
                'objectives' => [
                    'View and filter search history',
                    'Analyze search performance metrics',
                    'Re-run previous searches',
                    'Export historical data'
                ]
            ],
            [
                'key' => 'feedback',
                'title' => 'Feedback & Support',
                'description' => 'Share feedback and get help when you need it',
                'duration' => '4 min',
                'category' => 'advanced',
                'order' => 6,
                'icon' => 'comments',
                'color' => 'pink',
                'youtube_id' => null,
                'objectives' => [
                    'Submit feedback and suggestions',
                    'Rate your experience',
                    'View feedback history',
                    'Contact support when needed'
                ]
            ],
            [
                'key' => 'profile',
                'title' => 'Profile & Security Settings',
                'description' => 'Customize your account and security preferences',
                'duration' => '5 min',
                'category' => 'advanced',
                'order' => 7,
                'icon' => 'user-cog',
                'color' => 'teal',
                'youtube_id' => null,
                'objectives' => [
                    'Update personal information',
                    'Change password and security settings',
                    'Configure notification preferences',
                    'Manage account privacy settings'
                ]
            ]
        ];
    }

    /**
     * Update tutorial with YouTube video ID
     */
    public function updateTutorial(Request $request, $tutorialKey)
    {
        $request->validate([
            'youtube_id' => 'required|string'
        ]);

        // This could be stored in database or config file
        // For now, you'd need to manually update the getTutorialData method
        
        return response()->json(['success' => true]);
    }
}