<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\UserFeedback;

class FeedbackController extends Controller
{
    /**
     * Store feedback
     */
    public function store(Request $request)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'feedback_type' => 'required|in:suggestion,bug,feature,general',
            'message' => 'required|string|min:10|max:1000',
            'contact_permission' => 'nullable|boolean'
        ]);

        try {
            UserFeedback::create([
                'user_id' => Auth::id(),
                'rating' => $request->rating,
                'feedback_type' => $request->feedback_type,
                'message' => trim($request->message),
                'contact_permission' => $request->has('contact_permission'),
                'user_agent' => $request->userAgent(),
                'ip_address' => $request->ip(),
                'status' => 'pending'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Thank you for your feedback! We appreciate your input and will review it soon.'
            ]);

        } catch (\Exception $e) {
            \Log::error('Feedback submission error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to submit feedback. Please try again later.'
            ], 500);
        }
    }



    /**
     * User feedback history with filters and stats
     */
    public function userFeedback(Request $request)
    {
        $user = Auth::user();
        $query = UserFeedback::where('user_id', $user->id);

        // Apply filters
        if ($request->has('type') && $request->type) {
            $query->where('feedback_type', $request->type);
        }

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('rating') && $request->rating) {
            $query->where('rating', $request->rating);
        }

        // Get paginated feedback
        $feedback = $query->orderBy('created_at', 'desc')->paginate(10);

        // Calculate user stats
        $stats = [
            'total' => UserFeedback::where('user_id', $user->id)->count(),
            'pending' => UserFeedback::where('user_id', $user->id)->where('status', 'pending')->count(),
            'reviewed' => UserFeedback::where('user_id', $user->id)->where('status', 'reviewed')->count(),
            'resolved' => UserFeedback::where('user_id', $user->id)->where('status', 'resolved')->count(),
            'avg_rating' => UserFeedback::where('user_id', $user->id)->avg('rating') ?: 0
        ];

        return view('user.feedback_history', compact('feedback','stats', 'user'));
    }

    /**
     * Admin: View all feedback
     */
    public function adminIndex(Request $request)
    {
        $query = UserFeedback::with('user');

        // Filter by type
        if ($request->has('type') && $request->type) {
            $query->where('feedback_type', $request->type);
        }

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter by rating
        if ($request->has('rating') && $request->rating) {
            $query->where('rating', $request->rating);
        }

        $feedback = $query->orderBy('created_at', 'desc')->paginate(15);

        $stats = [
            'total' => UserFeedback::count(),
            'pending' => UserFeedback::where('status', 'pending')->count(),
            'high_rated' => UserFeedback::where('rating', '>=', 4)->count(),
            'low_rated' => UserFeedback::where('rating', '<=', 2)->count(),
            'avg_rating' => round(UserFeedback::avg('rating'), 1)
        ];

        return view('admin.feedback.index', compact('feedback', 'stats'));
    }

    /**
     * Admin: Update feedback status
     */
    public function updateStatus(Request $request, UserFeedback $feedback)
    {
        $request->validate([
            'status' => 'required|in:pending,reviewed,resolved'
        ]);

        $feedback->update([
            'status' => $request->status
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Feedback status updated successfully.'
        ]);
    }
}