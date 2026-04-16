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
        
        // Calculate total duration (format: "M:SS min")
        $totalSeconds = array_sum(array_map(function($tutorial) {
            $time = trim(str_replace('min', '', $tutorial['duration']));
            $parts = explode(':', $time);
            $minutes = isset($parts[0]) ? (int) $parts[0] : 0;
            $seconds = isset($parts[1]) ? (int) $parts[1] : 0;
            return ($minutes * 60) + $seconds;
        }, $tutorials));
        $totalDuration = round($totalSeconds / 60);

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
                'key' => 'add-extension',
                'title' => 'How to Add Extension',
                'description' => 'CustomerNearme Chrome Extension install aur setup karna',
                'duration' => '2:17 min',
                'category' => 'getting-started',
                'order' => 1,
                'icon' => 'puzzle-piece',
                'color' => 'indigo',
                'youtube_id' => 'fIhGuN3bW78',
                'objectives' => [
                    'Extension ZIP file download karna',
                    'Chrome Extensions page kholna',
                    'Developer mode enable karna',
                    'Extension load aur activate karna'
                ]
            ],
            [
                'key' => 'subscription',
                'title' => 'CustomerNearMe Subscription Plans',
                'description' => 'Subscription plans dekhein, upgrade karein aur billing manage karein',
                'duration' => '2:11 min',
                'category' => 'getting-started',
                'order' => 2,
                'icon' => 'crown',
                'color' => 'yellow',
                'youtube_id' => 'JLk_nhsCggE',
                'objectives' => [
                    'Available subscription plans compare karna',
                    'Plan upgrade ya downgrade karna',
                    'Billing history dekhna',
                    'Payment method manage karna'
                ]
            ],
            [
                'key' => 'find-leads',
                'title' => 'Find Leads from Google Maps',
                'description' => 'Google Maps se business leads dhundhne ka complete tarika',
                'duration' => '4:00 min',
                'category' => 'getting-started',
                'order' => 3,
                'icon' => 'search-location',
                'color' => 'green',
                'youtube_id' => 'D9zkAuRRo8A',
                'objectives' => [
                    'Business keyword aur location se search karna',
                    'Category aur rating filters lagana',
                    'Search results mein se leads select karna',
                    'Leads ko save karna apne collection mein'
                ]
            ],
            [
                'key' => 'my-leads',
                'title' => 'Manage & Export Leads Easily',
                'description' => 'Saved leads ko manage, filter aur export karna',
                'duration' => '7:17 min',
                'category' => 'getting-started',
                'order' => 4,
                'icon' => 'bookmark',
                'color' => 'orange',
                'youtube_id' => 'nlHkNf_BkxM',
                'objectives' => [
                    'Saved leads ki list dekhna aur filter karna',
                    'Lead details aur contact information access karna',
                    'Leads ko CSV format mein export karna',
                    'Unwanted leads delete karna'
                ]
            ],
            [
                'key' => 'dashboard',
                'title' => 'CustomerNearMe Dashboard',
                'description' => 'Dashboard ka overview samjhein — stats, activity aur quick actions',
                'duration' => '2:09 min',
                'category' => 'getting-started',
                'order' => 5,
                'icon' => 'home',
                'color' => 'blue',
                'youtube_id' => 'NkPv0nv6Kf0',
                'objectives' => [
                    'Dashboard interface navigate karna',
                    'Total leads, searches aur subscription stats dekhna',
                    'Recent activity monitor karna',
                    'Quick action buttons ka use karna'
                ]
            ],
            [
                'key' => 'profile',
                'title' => 'Profile Settings Guide',
                'description' => 'Account information, password aur preferences update karna',
                'duration' => '1:11 min',
                'category' => 'advanced',
                'order' => 6,
                'icon' => 'user-cog',
                'color' => 'purple',
                'youtube_id' => 'Q2TgEQh6XSU',
                'objectives' => [
                    'Name, email aur profile picture update karna',
                    'Password change karna',
                    'Notification preferences set karna',
                    'Account security settings configure karna'
                ]
            ],
            [
                'key' => 'feedback',
                'title' => 'Send Feedback & Contact Support',
                'description' => 'Apna feedback submit karein aur support se rabta karein',
                'duration' => '2:02 min',
                'category' => 'advanced',
                'order' => 7,
                'icon' => 'comments',
                'color' => 'pink',
                'youtube_id' => '8Cb_L-LmezA',
                'objectives' => [
                    'Feedback form fill karna',
                    'Star rating dena',
                    'Bug report ya suggestion submit karna',
                    'Support team se contact karna'
                ]
            ],
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