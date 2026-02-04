<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\LeadsController;
use App\Http\Controllers\ApiKeysController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TutorialsController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Home page
Route::get('/', [HomeController::class, 'index'])->name('home');

// Redirect all requests to /login → auth.show
Route::get('/login', function () {
    return redirect()->route('auth.show');
})->name('login');


// Authentication routes (no auth required)
Route::prefix('auth')->name('auth.')->group(function () {
    Route::get('/', [AuthController::class, 'showAuth'])->name('show');
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/signup', [AuthController::class, 'signup'])->name('signup');
    Route::get('/google', [AuthController::class, 'redirectToGoogle'])->name('google');
    Route::get('/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('google.callback');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/check', [AuthController::class, 'checkAuth'])->name('check');
    
    // Email Verification
    Route::get('/verify-email/{token}', [AuthController::class, 'verifyEmail'])->name('verify.email');
    Route::post('/resend-verification', [AuthController::class, 'resendVerification'])->name('resend.verification');
    
    // Password Reset
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('forgot.password');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('send.reset.link');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('reset.password');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('reset.password.submit');
});

// Protected Routes (require authentication)
Route::middleware(['web', 'auth'])->group(function () {
    
    // General dashboard route
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // User Routes
        Route::prefix('user')->name('user.')->group(function () {
            Route::get('/dashboard', [DashboardController::class, 'userDashboard'])->name('dashboard');
            Route::post('/welcome-tutorial/mark-seen', [DashboardController::class, 'markWelcomeTutorialSeen'])
     ->name('welcome-tutorial.mark-seen');
            
            // Profile routes
            Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
            Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
            Route::post('/avatar/upload', [ProfileController::class, 'uploadAvatar'])->name('avatar.upload');
            Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('password.update');
            Route::put('/profile/preferences', [ProfileController::class, 'updatePreferences'])->name('preferences.update');
            Route::delete('/profile/account', [ProfileController::class, 'deleteAccount'])->name('account.delete');
            Route::delete('/profile/data', [ProfileController::class, 'clearData'])->name('data.clear');
            Route::post('/profile/verify-email', [ProfileController::class, 'resendVerification'])->name('verification.send');
        
            // Tutorial routes
            Route::get('/tutorials', [TutorialsController::class, 'index'])->name('tutorials');
            Route::post('/tutorials/complete', [TutorialsController::class, 'markCompleted'])->name('tutorials.complete');
            Route::post('/tutorials/reset', [TutorialsController::class, 'resetProgress'])->name('tutorials.reset');
            Route::put('/tutorials/{tutorialKey}/update', [TutorialsController::class, 'updateTutorial'])->name('tutorials.update');
            
            // Search Places
            Route::get('/search', [SearchController::class, 'index'])->name('search');
            Route::post('/search', [SearchController::class, 'search'])->name('search.post');
                        // Add this route for saving leads
            Route::post('/leads/save', [SearchController::class, 'saveLeads'])->name('leads.save');

            Route::get('/api/states/{country}', [SearchController::class, 'getStates'])->name('api.states');
            Route::get('/api/cities/{state}', [SearchController::class, 'getCities'])->name('api.cities');
            Route::get('/api/place-details/{placeId}', [SearchController::class, 'getPlaceDetails'])->name('api.place-details');

            // Leads routes
            Route::get('/leads', [LeadsController::class, 'index'])->name('leads');
            Route::get('/leads/{id}', [LeadsController::class, 'show'])->name('leads.show');
            Route::post('/leads/{id}/status', [LeadsController::class, 'updateStatus'])->name('leads.status');
            Route::post('/leads/{id}/notes', [LeadsController::class, 'updateNotes'])->name('leads.notes');
            Route::delete('/leads/{id}', [LeadsController::class, 'destroy'])->name('leads.delete');
            Route::post('/leads/bulk', [LeadsController::class, 'bulkAction'])->name('leads.bulk');


            Route::get('/debug-reviews', [LeadsController::class, 'debugReviews'])->name('debug.reviews');

            // API Keys
            Route::get('/api-keys', [ApiKeysController::class, 'index'])->name('api-keys');
            Route::post('/api-keys', [ApiKeysController::class, 'store'])->name('api-keys.store');
            Route::put('/api-keys/{id}', [ApiKeysController::class, 'update'])->name('api-keys.update');
            Route::post('/api-keys/{id}/toggle', [ApiKeysController::class, 'toggle'])->name('api-keys.toggle');
            Route::post('/api-keys/{id}/test', [ApiKeysController::class, 'test'])->name('api-keys.test');
            Route::delete('/api-keys/{id}', [ApiKeysController::class, 'destroy'])->name('api-keys.delete');
            // routes/web.php or routes/api.php
            Route::post('/api/test-api-key', [ApiKeysController::class, 'testApiKey'])->name('test-api-keys');
            
            // Search History
            Route::get('/search-history', [SearchController::class, 'history'])->name('search-history');
            Route::post('/rerun-search', [SearchController::class, 'rerunSearch'])->name('rerun-search');
            Route::delete('/search-history/{id}', [SearchController::class, 'deleteSearchHistory'])->name('delete-search-history');
            Route::get('/search-results/{id}', [SearchController::class, 'viewSearchResults'])->name('view-search-results');
            Route::get('/export-search-history', [SearchController::class, 'exportSearchHistory'])->name('export-search-history');

            
            // Subscription
            Route::get('/subscription', [SubscriptionController::class, 'index'])->name('subscription');
            Route::post('/subscription/upgrade', [SubscriptionController::class, 'upgrade'])->name('subscription.upgrade');


            Route::post('/feedback', [FeedbackController::class, 'store'])->name('feedback.store');
            Route::get('/feedback/history', [FeedbackController::class, 'userFeedback'])->name('feedback.history');

        });
    
    // Admin Routes
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'adminDashboard'])->name('dashboard');
        Route::get('/users', [DashboardController::class, 'adminUsers'])->name('users');
    });
});