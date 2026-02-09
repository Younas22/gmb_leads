<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\SavedLead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class ProfileController extends Controller
{
    /**
     * Show the user profile page
     */
    public function show()
    {
        $user = Auth::user();
        
        // Get user statistics
        $stats = [
            'total_searches' => $user->searchHistories()->count(),
            'saved_leads' => $user->savedLeads()->count(),
            'successful_searches' => $user->searchHistories()->where('status', 'success')->count(),
            'avg_results' => $user->searchHistories()->avg('results_count') ?? 0,
        ];
        
        // Get recent leads
        $recentLeads = $user->savedLeads()
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();
        
        return view('user.profile', compact('user', 'stats', 'recentLeads'));
    }

    /**
     * Update user profile information
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        // Debug Step 1: Check all form data
        \Log::info('User update request received', $request->all());

        // Debug Step 2: Check if file exists in request
        if ($request->hasFile('avatar')) {
            \Log::info('Avatar file found in request', [
                'original_name' => $request->file('avatar')->getClientOriginalName(),
                'mime_type' => $request->file('avatar')->getMimeType(),
                'size' => $request->file('avatar')->getSize(),
            ]);
        } else {
            \Log::warning('No avatar file found in request');
        }

        // Validation rules
        $rules = [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
        ];

        // Only allow email update for non-team members
        if (!$user->isTeamMember()) {
            $rules['email'] = ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)];
        }

        $request->validate($rules);

        $data = [
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'name' => $request->first_name . ' ' . $request->last_name,
        ];

        // Only update email for non-team members
        if (!$user->isTeamMember() && $request->has('email')) {
            $data['email'] = $request->email;
        }

        if ($request->hasFile('avatar')) {
            $destinationPath = public_path('assets/avatar');

            // Delete old avatar if exists
            if ($user->avatar && file_exists(public_path($user->avatar))) {
                unlink(public_path($user->avatar));
            }

            $file = $request->file('avatar');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move($destinationPath, $filename);

            $data['avatar'] = 'assets/avatar/' . $filename;

            // Debug Step 3: Confirm upload success
            \Log::info('Avatar uploaded successfully', ['path' => $data['avatar']]);
        }

        // Only check email verification for non-team members
        if (!$user->isTeamMember() && $request->has('email') && $user->email !== $request->email && $user->login_type !== User::LOGIN_GOOGLE) {
            $data['email_verified'] = false;
            $data['email_verification_token'] = \Str::random(64);
        }

        $user->update($data);

        return redirect()->route('user.profile')->with('success', 'Profile updated successfully!');
    }


    public function uploadAvatar(Request $request)
    {
        $user = Auth::user();

        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $destinationPath = public_path('assets/avatar');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move($destinationPath, $filename);

            // Delete old avatar if exists
            if ($user->avatar && file_exists(public_path($user->avatar))) {
                unlink(public_path($user->avatar));
            }

            $user->update(['avatar' => 'assets/avatar/' . $filename]);

            return response()->json([
                'success' => true,
                'message' => 'Profile picture updated successfully!',
                'avatar' => asset('public/assets/avatar/' . $filename)
            ]);
        }

        return response()->json(['success' => false, 'message' => 'No file uploaded.'], 400);
    }



    /**
     * Update user password
     */
    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        // Check if user uses Google login
        if ($user->login_type === User::LOGIN_GOOGLE) {
            return redirect()->route('user.profile')->with('error', 'Cannot change password for Google accounts.');
        }

        $request->validate([
            'current_password' => ['required'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        // Check current password
        if (!Hash::check($request->current_password, $user->password)) {
            return redirect()->route('user.profile')->with('error', 'Current password is incorrect.');
        }

        $user->update([
            'password' => Hash::make($request->password),
            'plain_password' => $request->password, // If you're storing plain passwords (not recommended)
        ]);

        return redirect()->route('user.profile')->with('success', 'Password updated successfully!');
    }

    /**
     * Update user preferences
     */
    public function updatePreferences(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'notifications' => ['nullable', 'array'],
            'default_location' => ['nullable', 'string', 'max:255'],
            'results_per_page' => ['required', 'integer', 'in:10,25,50,100'],
        ]);

        // Store preferences in user preferences table or user meta
        // For now, we'll store in a JSON column or separate table
        $preferences = [
            'notifications' => $request->notifications ?? [],
            // 'default_location' => $request->default_location,
            // 'results_per_page' => $request->results_per_page,
        ];

        // You might want to create a separate UserPreferences model
        // For now, we'll store in a preferences column (add migration if needed)
        $user->update(['preferences' => json_encode($preferences)]);

        return redirect()->route('user.profile')->with('success', 'Preferences updated successfully!');
    }

    /**
     * Delete user account
     */
    public function deleteAccount(Request $request)
    {
        $user = Auth::user();

        // Delete user's avatar if exists
        if ($user->avatar && Storage::disk('public')->exists('avatars/' . basename($user->avatar))) {
            Storage::disk('public')->delete('avatars/' . basename($user->avatar));
        }

        // Delete user's saved leads
        $user->savedLeads()->delete();

        // Delete user's search history
        $user->searchHistories()->delete();

        // Delete user account
        $user->delete();

        // Logout user
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'Account deleted successfully.');
    }

    /**
     * Clear user data (leads and search history)
     */
    public function clearData()
    {
        $user = Auth::user();

        // Delete user's saved leads
        $user->savedLeads()->delete();

        // Delete user's search history
        $user->searchHistories()->delete();

        return redirect()->route('user.profile')->with('success', 'All data cleared successfully!');
    }

    /**
     * Resend email verification
     */
    public function resendVerification(Request $request)
    {
        $user = Auth::user();

        if ($user->email_verified) {
            return redirect()->route('user.profile')->with('info', 'Email is already verified.');
        }

        // Generate new verification token
        $user->update([
            'email_verification_token' => \Str::random(64),
        ]);

        // Send verification email (implement your email sending logic)
        // Mail::to($user->email)->send(new EmailVerificationMail($user));

        return redirect()->route('user.profile')->with('success', 'Verification email sent successfully!');
    }
}