<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Package;
use App\Models\Subscription;
use App\Services\AffiliateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Resend\Laravel\Facades\Resend;
use Exception;

class AuthController extends Controller
{
    // Show login/signup page
    public function showAuth()
    {
        if (Auth::check()) {
            return $this->redirectToDashboard();
        }
        
        return view('auth.login');
    }

    // Handle regular login
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $credentials = $request->only('email', 'password');

        // Find user by email
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found with this email address.'
            ], 404);
        }

        if ($user->status !== User::STATUS_ACTIVE) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is inactive. Please contact support.'
            ], 403);
        }

        if (Auth::attempt($credentials, $request->has('remember'))) {
            $request->session()->regenerate();
            
            // Update last login
            $user->updateLastLogin();

            return response()->json([
                'success' => true,
                'message' => 'Login successful!',
                'redirect' => $this->getDashboardUrl($user)
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid email or password.'
        ], 401);
    }

    // Handle regular signup
    public function signup(Request $request)
    {
        \Log::info('Signup API hit', $request->all());

        // Check if registration is allowed
        if (!\App\Models\Setting::get('allow_registration', true)) {
            return response()->json([
                'success' => false,
                'message' => 'User registration is currently disabled. Please contact support.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:50',
            'last_name'  => 'required|string|max:50',
            'email'      => 'required|email|unique:users,email',
            'password'   => 'required|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            \Log::error('Validation failed: ' . json_encode([
                'errors' => $validator->errors()->toArray(),
                'input'  => $request->all()
            ], JSON_PRETTY_PRINT));

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            $verificationToken = Str::random(64);

            $user = User::create([
                'first_name'              => $request->first_name,
                'last_name'               => $request->last_name,
                'name'                    => $request->first_name . ' ' . $request->last_name,
                'email'                   => $request->email,
                'password'                => Hash::make($request->password),
                'plain_password'          => $request->password,
                'login_type'              => User::LOGIN_REGULAR,
                'user_type'               => User::TYPE_USER,
                'status'                  => User::STATUS_ACTIVE,
                'email_verified'          => false,
                'email_verification_token'=> $verificationToken,
                'last_login'              => now(),
            ]);

            // Assign a referral code to the new user
            AffiliateService::ensureReferralCode($user);

            // Track referral — read BEFORE session regenerate (session is cleared after regenerate)
            $refCode = AffiliateService::getReferralCodeFromRequest($request);
            AffiliateService::handleSignup($user, $refCode);

            // Send verification email only if email verification is enabled
            if (\App\Models\Setting::get('enable_verify_email', false)) {
                $this->sendVerificationEmail($user);
            }

            // Auto login after signup
            Auth::login($user);
            $request->session()->regenerate();

            // Clear ref session after successful signup (prevent reuse)
            AffiliateService::clearReferralSession($request);

            // Give new user a 3-day free trial
            $this->assignFreeTrial($user);

            return response()->json([
                'success' => true,
                'message' => 'Account created! Please check your email to verify your account.',
                'redirect'=> $this->getDashboardUrl($user)
            ]);

        } catch (\Exception $e) {
            \Log::error("Signup failed: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create account. Please try again.'
            ], 500);
        }
    }

    // Send verification email
    private function sendVerificationEmail($user)
    {
        try {
            $verificationUrl = route('auth.verify.email', [
                'token' => $user->email_verification_token
            ]);

            $result = \App\Services\EmailService::sendVerificationEmail($user, $verificationUrl);

            if ($result['success']) {
                Log::info('Verification email sent to: ' . $user->email);
            } else {
                Log::info($result['message']);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send verification email: ' . $e->getMessage());
        }
    }

    // Verify email
    public function verifyEmail($token)
    {
        $user = User::where('email_verification_token', $token)->first();

        if (!$user) {
            return redirect()->route('auth.show')
                ->with('error', 'Invalid verification link.');
        }

        if ($user->email_verified) {
            // If user is already logged in, redirect to dashboard
            if (Auth::check() && Auth::id() === $user->id) {
                return redirect($this->getDashboardUrl($user))
                    ->with('success', 'Your email is already verified!');
            }
            return redirect()->route('auth.show')
                ->with('success', 'Your email is already verified. Please login.');
        }

        $user->update([
            'email_verified' => true,
            'email_verification_token' => null,
            'email_verified_at' => now()
        ]);

        // Send welcome email after successful verification
        try {
            \App\Services\EmailService::sendWelcomeEmail($user);
            Log::info('Welcome email sent to user ' . $user->id . ' after email verification');
        } catch (\Exception $e) {
            Log::error('Welcome email failed for user ' . $user->id . ': ' . $e->getMessage());
        }

        // If user is logged in, redirect to dashboard
        if (Auth::check() && Auth::id() === $user->id) {
            return redirect($this->getDashboardUrl($user))
                ->with('success', 'Email verified successfully! Welcome to ' . config('app.name') . '!');
        }

        // If not logged in, redirect to login page
        return redirect()->route('auth.show')
            ->with('success', 'Email verified successfully! You can now login.');
    }

    // Resend verification email
    public function resendVerification(Request $request)
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Please login first.'
            ], 401);
        }

        // Check if email verification is enabled
        if (!\App\Models\Setting::get('enable_verify_email', false)) {
            return response()->json([
                'success' => false,
                'message' => 'Email verification is currently disabled.'
            ], 403);
        }

        $user = Auth::user();

        if ($user->email_verified) {
            return response()->json([
                'success' => false,
                'message' => 'Your email is already verified.'
            ], 400);
        }

        // Generate new token
        $user->update([
            'email_verification_token' => Str::random(64)
        ]);

        $this->sendVerificationEmail($user);

        return response()->json([
            'success' => true,
            'message' => 'Verification email sent! Please check your inbox.'
        ]);
    }

    // Show forgot password form
    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    // Send password reset link
    public function sendResetLink(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Email not found in our system.',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::where('email', $request->email)->first();
            
            // Generate reset token
            $token = Str::random(64);
            
            // Store in password_resets table
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $user->email],
                [
                    'token' => Hash::make($token),
                    'created_at' => now()
                ]
            );

            // Send reset email
            $resetUrl = route('auth.reset.password', ['token' => $token, 'email' => $user->email]);

            $result = \App\Services\EmailService::sendPasswordResetEmail($user, $resetUrl);

            if (!$result['success']) {
                Log::warning($result['message']);
            }

            return response()->json([
                'success' => true,
                'message' => 'Password reset link sent to your email!'
            ]);

        } catch (\Exception $e) {
            Log::error('Password reset email failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to send reset link. Please try again.'
            ], 500);
        }
    }

    // Show reset password form
    public function showResetPassword($token, Request $request)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->email
        ]);
    }

    // Reset password
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'password' => 'required|min:8|confirmed',
            'token' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Verify token
        $passwordReset = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$passwordReset || !Hash::check($request->token, $passwordReset->token)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired reset token.'
            ], 400);
        }

        // Check if token is expired (24 hours)
        if (now()->diffInHours($passwordReset->created_at) > 24) {
            return response()->json([
                'success' => false,
                'message' => 'Reset link has expired. Please request a new one.'
            ], 400);
        }

        // Update password
        $user = User::where('email', $request->email)->first();
        $user->update([
            'password' => Hash::make($request->password),
            'plain_password' => $request->password
        ]);

        // Delete reset token
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Password reset successfully! You can now login.'
        ]);
    }

    // Google OAuth
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            Log::info('Google User Data', [
                'id'    => $googleUser->getId(),
                'name'  => $googleUser->getName(),
                'email' => $googleUser->getEmail(),
                'avatar'=> $googleUser->getAvatar(),
            ]);

            $existingUser = User::where('email', $googleUser->getEmail())->first();

            if ($existingUser) {
                if (!$existingUser->google_id) {
                    $existingUser->update([
                        'google_id' => $googleUser->getId(),
                        'avatar'    => $googleUser->getAvatar(),
                        'email_verified' => true, // Google emails are verified
                    ]);
                }

                if ($existingUser->status !== User::STATUS_ACTIVE) {
                    return redirect()->route('auth.show')
                        ->with('error', 'Your account is inactive. Please contact support.');
                }

                Auth::login($existingUser);
                if (method_exists($existingUser, 'updateLastLogin')) {
                    $existingUser->updateLastLogin();
                }

                return redirect($this->getDashboardUrl($existingUser));
            }

            // Check if registration is allowed for new users
            if (!\App\Models\Setting::get('allow_registration', true)) {
                return redirect()->route('auth.show')
                    ->with('error', 'User registration is currently disabled. Please contact support.');
            }

            // New user - create with temporary user_type
            $plainPassword = Str::random(10);

            $user = User::create([
                'first_name'     => $googleUser->user['given_name'] ?? null,
                'last_name'      => $googleUser->user['family_name'] ?? null,
                'name'           => $googleUser->getName(),
                'email'          => $googleUser->getEmail(),
                'google_id'      => $googleUser->getId(),
                'avatar'         => $googleUser->getAvatar(),
                'login_type'     => 'google',
                'user_type'      => User::TYPE_USER,
                'status'         => User::STATUS_ACTIVE,
                'email_verified' => true, // Google emails are verified
                'last_login'     => now(),
                'plain_password' => $plainPassword,
                'password'       => bcrypt($plainPassword),
            ]);

            Auth::login($user);

            // Give new Google user a 3-day free trial
            $this->assignFreeTrial($user);

            // Assign referral code + handle referral attribution for Google signups
            AffiliateService::ensureReferralCode($user);
            $refCode = request()->cookie('ref_code');
            AffiliateService::handleSignup($user, $refCode);

            return $this->redirectToDashboard();

        } catch (Exception $e) {
            Log::error('Google Login Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('auth.show')
                ->with('error', 'Failed to login with Google. Please try again.');
        }
    }

    // Show account type selection page
    public function showAccountTypeSelection()
    {
        if (!Auth::check()) {
            return redirect()->route('auth.show')
                ->with('error', 'Please login first.');
        }

        $user = Auth::user();

        return view('auth.choose-account-type', compact('user'));
    }

    // Save selected account type
    public function saveAccountType(Request $request)
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Please login first.'
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'user_type' => 'required|in:user,company',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid account type selected.',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if company registration is allowed
        if ($request->user_type === 'company' && !\App\Models\Setting::get('allow_company_registration', true)) {
            return response()->json([
                'success' => false,
                'message' => 'Company registration is currently disabled. Please contact support.'
            ], 403);
        }

        try {
            $user = Auth::user();

            $user->update([
                'user_type' => $request->user_type
            ]);

            Log::info('Account type updated', [
                'user_id' => $user->id,
                'user_type' => $request->user_type
            ]);

            // Send welcome email for Google OAuth users
            if ($user->login_type === 'google') {
                try {
                    \App\Services\EmailService::sendWelcomeEmail($user);
                } catch (\Exception $e) {
                    Log::error('Welcome email failed for user ' . $user->id . ': ' . $e->getMessage());
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Account type saved successfully!',
                'redirect' => $this->getDashboardUrl($user)
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to save account type: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to save account type. Please try again.'
            ], 500);
        }
    }

    // Logout
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully'
            ]);
        }

        return redirect()->route('auth.show')->with('success', 'You have been logged out successfully.');
    }

    // Assign a 3-day free trial to a newly registered user
    private function assignFreeTrial(User $user): void
    {
        try {
            $packageFor = $user->isCompany() ? 'company' : 'user';

            $package = Package::active()
                ->where('package_for', $packageFor)
                ->orderBy('price', 'asc')
                ->first();

            if (!$package) {
                return;
            }

            Subscription::create([
                'package_id'  => $package->id,
                'user_id'     => $user->id,
                'amount_paid' => 0,
                'start_date'  => now(),
                'end_date'    => now()->addDays(3),
                'status'      => 'active',
                'is_trial'    => true,
            ]);
        } catch (\Exception $e) {
            Log::error('Free trial assignment failed for user ' . $user->id . ': ' . $e->getMessage());
        }
    }

    // Helper methods
    private function getDashboardUrl($user)
    {
        if ($user->isAdmin()) {
            return route('admin.dashboard');
        }

        // Check if user has active subscription
        if ($user->hasRestrictedAccess()) {
            return route('user.subscription');
        }

        return route('user.dashboard');
    }

    private function redirectToDashboard()
    {
        $user = Auth::user();
        return redirect($this->getDashboardUrl($user));
    }

    public function checkAuth()
    {
        if (Auth::check()) {
            return response()->json([
                'authenticated' => true,
                'user' => [
                    'id' => Auth::user()->id,
                    'name' => Auth::user()->name,
                    'email' => Auth::user()->email,
                    'user_type' => Auth::user()->user_type,
                    'avatar' => Auth::user()->avatar,
                    'email_verified' => Auth::user()->email_verified,
                ]
            ]);
        }

        return response()->json(['authenticated' => false]);
    }
}