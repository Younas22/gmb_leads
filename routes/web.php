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
use App\Http\Controllers\Admin\PackageController;
use App\Http\Controllers\Admin\SubscriptionController as AdminSubscriptionController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\ReportController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Home page
Route::get('/', [HomeController::class, 'index'])->name('home');

// Policy pages
Route::get('/terms-and-conditions', fn() => view('pages.terms'))->name('terms');
Route::get('/privacy-policy', fn() => view('pages.privacy'))->name('privacy.policy');
Route::get('/refund-policy', fn() => view('pages.refund'))->name('refund.policy');

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

    // Account Type Selection (for Google OAuth new users)
    Route::get('/choose-account-type', [AuthController::class, 'showAccountTypeSelection'])->name('auth.choose.account.type');
    Route::post('/save-account-type', [AuthController::class, 'saveAccountType'])->name('auth.save.account.type');

    // General dashboard route
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // User Routes
        Route::prefix('user')->name('user.')->group(function () {
            // Subscription - always accessible (so users can select/upgrade packages)
            Route::get('/subscription', [SubscriptionController::class, 'index'])->name('subscription');
            Route::post('/subscription/upgrade', [SubscriptionController::class, 'upgrade'])->name('subscription.upgrade');
            Route::post('/payment/submit', [SubscriptionController::class, 'submitPayment'])->name('payment.submit');

            // Feedback - always accessible (so users can provide feedback regardless of subscription)
            Route::post('/feedback', [FeedbackController::class, 'store'])->name('feedback.store');
            Route::get('/feedback/history', [FeedbackController::class, 'userFeedback'])->name('feedback.history');

            // Restricted routes - only accessible if user has active subscription
            Route::middleware('subscription.access')->group(function () {
                // Dashboard
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
                Route::get('/leads/export', [LeadsController::class, 'export'])->name('leads.export');
                Route::get('/leads/export-excel', [LeadsController::class, 'exportExcel'])->name('leads.export.excel');
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

                // Team Members (for company accounts)
                Route::get('/team-members', [App\Http\Controllers\TeamMembersController::class, 'index'])->name('team-members');
                Route::post('/team-members', [App\Http\Controllers\TeamMembersController::class, 'store'])->name('team-members.store');
                Route::get('/team-members/{id}', [App\Http\Controllers\TeamMembersController::class, 'show'])->name('team-members.show');
                Route::put('/team-members/{id}', [App\Http\Controllers\TeamMembersController::class, 'update'])->name('team-members.update');
                Route::post('/team-members/{id}/toggle-status', [App\Http\Controllers\TeamMembersController::class, 'toggleStatus'])->name('team-members.toggle-status');
                Route::delete('/team-members/{id}', [App\Http\Controllers\TeamMembersController::class, 'destroy'])->name('team-members.delete');

                // Search History
                Route::get('/search-history', [SearchController::class, 'history'])->name('search-history');
                Route::post('/rerun-search', [SearchController::class, 'rerunSearch'])->name('rerun-search');
                Route::delete('/search-history/{id}', [SearchController::class, 'deleteSearchHistory'])->name('delete-search-history');
                Route::get('/search-results/{id}', [SearchController::class, 'viewSearchResults'])->name('view-search-results');
                Route::get('/export-search-history', [SearchController::class, 'exportSearchHistory'])->name('export-search-history');
            });
        });
    
    // Admin Routes
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'adminDashboard'])->name('dashboard');
        Route::get('/switch-to-user', [DashboardController::class, 'switchToUserView'])->name('switch.to.user');
        Route::get('/switch-to-admin', [DashboardController::class, 'switchToAdminView'])->name('switch.to.admin');
        Route::get('/users', [DashboardController::class, 'adminUsers'])->name('users');
        Route::get('/users/{user}', [DashboardController::class, 'showUser'])->name('users.show');
        Route::put('/users/{user}', [DashboardController::class, 'updateUser'])->name('users.update');
        Route::delete('/users/{user}', [DashboardController::class, 'deleteUser'])->name('users.delete');
        Route::post('/users/{user}/toggle-signups', [DashboardController::class, 'toggleSignups'])->name('users.toggle.signups');

        // Packages CRUD
        Route::get('/packages', [PackageController::class, 'index'])->name('packages.index');
        Route::post('/packages', [PackageController::class, 'store'])->name('packages.store');
        Route::get('/packages/{package}/edit', [PackageController::class, 'edit'])->name('packages.edit');
        Route::put('/packages/{package}', [PackageController::class, 'update'])->name('packages.update');
        Route::delete('/packages/{package}', [PackageController::class, 'destroy'])->name('packages.destroy');
        Route::post('/packages/{package}/toggle-status', [PackageController::class, 'toggleStatus'])->name('packages.toggle-status');

        // Subscriptions CRUD
        Route::get('/subscriptions', [AdminSubscriptionController::class, 'index'])->name('subscriptions.index');
        Route::post('/subscriptions', [AdminSubscriptionController::class, 'store'])->name('subscriptions.store');
        Route::get('/subscriptions/{subscription}/edit', [AdminSubscriptionController::class, 'edit'])->name('subscriptions.edit');
        Route::put('/subscriptions/{subscription}', [AdminSubscriptionController::class, 'update'])->name('subscriptions.update');
        Route::delete('/subscriptions/{subscription}', [AdminSubscriptionController::class, 'destroy'])->name('subscriptions.destroy');
        Route::post('/subscriptions/{subscription}/toggle-status', [AdminSubscriptionController::class, 'toggleStatus'])->name('subscriptions.toggle-status');

        // Payments
        Route::get('/payments', [AdminPaymentController::class, 'index'])->name('payments.index');
        Route::post('/payments/{payment}/status', [AdminPaymentController::class, 'updateStatus'])->name('payments.update-status');

        // Reports
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/revenue', [ReportController::class, 'revenue'])->name('revenue');
            Route::get('/users', [ReportController::class, 'userGrowth'])->name('users');
            Route::get('/leads', [ReportController::class, 'leads'])->name('leads');
            Route::get('/search', [ReportController::class, 'search'])->name('search');
            Route::get('/package-performance', [ReportController::class, 'packagePerformance'])->name('package-performance');
            Route::get('/export-activity', [ReportController::class, 'exportActivity'])->name('export-activity');
            Route::get('/top-performers', [ReportController::class, 'topPerformers'])->name('top-performers');
            Route::get('/user-activity', [ReportController::class, 'userActivity'])->name('user-activity');
            Route::get('/system-overview', [ReportController::class, 'systemOverview'])->name('system-overview');
            Route::get('/all-leads', [ReportController::class, 'allLeads'])->name('all-leads');

            // Export routes
            Route::get('/export/revenue', [ReportController::class, 'exportRevenue'])->name('export.revenue');
            Route::get('/export/users', [ReportController::class, 'exportUserGrowth'])->name('export.users');
            Route::get('/export/leads', [ReportController::class, 'exportLeads'])->name('export.leads');
            Route::get('/export/search', [ReportController::class, 'exportSearch'])->name('export.search');
            Route::get('/export/package-performance', [ReportController::class, 'exportPackagePerformance'])->name('export.package-performance');
            Route::get('/export/all-leads', [ReportController::class, 'exportAllLeads'])->name('export.all-leads');
        });

        // Feedback Management
        Route::prefix('feedback')->name('feedback.')->group(function () {
            Route::get('/history', [FeedbackController::class, 'adminIndex'])->name('history');
            Route::post('/{feedback}/status', [FeedbackController::class, 'updateStatus'])->name('update-status');
        });

        // Settings
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\SettingsController::class, 'index'])->name('index');

            // Email Settings
            Route::put('/email', [App\Http\Controllers\Admin\SettingsController::class, 'updateEmailSettings'])->name('email.update');
            Route::post('/email/verify', [App\Http\Controllers\Admin\SettingsController::class, 'verifyResendKey'])->name('email.verify');
            Route::post('/email/test', [App\Http\Controllers\Admin\SettingsController::class, 'sendTestEmail'])->name('email.test');
            Route::post('/email/test-new-feature', [App\Http\Controllers\Admin\SettingsController::class, 'sendTestNewFeatureEmail'])->name('email.test.new.feature');
            Route::post('/email/test-maintenance', [App\Http\Controllers\Admin\SettingsController::class, 'sendTestMaintenanceEmail'])->name('email.test.maintenance');
            Route::post('/email/bulk-new-feature', [App\Http\Controllers\Admin\SettingsController::class, 'sendBulkNewFeatureEmail'])->name('email.bulk.new.feature');
            Route::post('/email/bulk-maintenance', [App\Http\Controllers\Admin\SettingsController::class, 'sendBulkMaintenanceEmail'])->name('email.bulk.maintenance');
            Route::post('/email/toggle', [App\Http\Controllers\Admin\SettingsController::class, 'toggleEmailTemplate'])->name('email.toggle');
            Route::get('/email/verified-users-count', [App\Http\Controllers\Admin\SettingsController::class, 'getVerifiedUsersCount'])->name('email.verified.users.count');

            // Email Templates
            Route::get('/email-templates', [App\Http\Controllers\Admin\SettingsController::class, 'emailTemplates'])->name('email-templates.index');
            Route::get('/email-templates/{id}/edit', [App\Http\Controllers\Admin\SettingsController::class, 'editEmailTemplate'])->name('email-templates.edit');
            Route::put('/email-templates/{id}', [App\Http\Controllers\Admin\SettingsController::class, 'updateEmailTemplate'])->name('email-templates.update');
            Route::get('/email-templates/{id}/preview', [App\Http\Controllers\Admin\SettingsController::class, 'previewEmailTemplate'])->name('email-templates.preview');
            Route::post('/email-templates/{id}/reset', [App\Http\Controllers\Admin\SettingsController::class, 'resetEmailTemplate'])->name('email-templates.reset');

            // General Settings
            Route::put('/general', [App\Http\Controllers\Admin\SettingsController::class, 'updateGeneralSettings'])->name('general.update');
            Route::put('/api', [App\Http\Controllers\Admin\SettingsController::class, 'updateApiSettings'])->name('api.update');
            Route::put('/oauth', [App\Http\Controllers\Admin\SettingsController::class, 'updateGoogleOAuthSettings'])->name('oauth.update');
            Route::put('/system', [App\Http\Controllers\Admin\SettingsController::class, 'updateSystemSettings'])->name('system.update');

            // Cache & Database
            Route::post('/cache/clear', [App\Http\Controllers\Admin\SettingsController::class, 'clearCache'])->name('cache.clear');
            Route::post('/database/optimize', [App\Http\Controllers\Admin\SettingsController::class, 'optimizeDatabase'])->name('database.optimize');
            Route::post('/performance/seed-settings', [App\Http\Controllers\Admin\SettingsController::class, 'seedPerformanceSettings'])->name('performance.seed-settings');
            Route::post('/migrations/run', [App\Http\Controllers\Admin\SettingsController::class, 'runMigrations'])->name('migrations.run');

            // Composer Commands
            Route::post('/composer/run', [App\Http\Controllers\Admin\SettingsController::class, 'runComposerCommand'])->name('composer.run');
        });
    });
});