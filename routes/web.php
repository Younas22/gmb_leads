<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\LeadsController;
use App\Http\Controllers\LeadCenterController;
use App\Http\Controllers\LeadCenterImportController;
use App\Http\Controllers\LeadCenterFolderController;
use App\Http\Controllers\LeadCenterConversationController;
use App\Http\Controllers\LeadCenterResourceController;
use App\Http\Controllers\LeadCenterAccessController;
use App\Http\Controllers\ApiKeysController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TutorialsController;
use App\Http\Controllers\ExtensionController;
use App\Http\Controllers\Admin\PackageController;
use App\Http\Controllers\Admin\SubscriptionController as AdminSubscriptionController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ApiUsageController;
use App\Http\Controllers\AffiliateController;
use App\Http\Controllers\Admin\AffiliateController as AdminAffiliateController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Home page
Route::get('/', [HomeController::class, 'index'])->name('home');

// Public opt-out for outreach recipients (signed links; POST also serves RFC 8058 one-click)
Route::get('/email/unsubscribe/{email}', [App\Http\Controllers\EmailSending\UnsubscribeController::class, 'show'])
    ->whereNumber('email')->middleware(['signed', 'throttle:unsubscribe'])->name('email.unsubscribe');
Route::post('/email/unsubscribe/{email}', [App\Http\Controllers\EmailSending\UnsubscribeController::class, 'confirm'])
    ->whereNumber('email')->middleware(['signed', 'throttle:unsubscribe'])->name('email.unsubscribe.confirm');

// Phase 4: tracked-link redirect. The destination comes from the stored link record only.
Route::get('/r/{token}', App\Http\Controllers\EmailTracking\TrackedLinkController::class)
    ->where('token', '[A-Za-z0-9_-]{32}')->middleware('throttle:email-link')->name('email.link');

// Open pixel in Gmail emails sent with tracking on (signed; always returns the same 1x1 GIF)
Route::get('/o/{email}.gif', App\Http\Controllers\EmailTracking\OpenPixelController::class)
    ->whereNumber('email')->middleware('throttle:email-link')->name('email.open');

// Phase 5: customer Resend webhooks (Svix-signed; the token only selects the customer's config)
Route::post('/webhooks/resend/{token}', App\Http\Controllers\Resend\ResendWebhookController::class)
    ->where('token', '[A-Za-z0-9]{48}')->middleware('throttle:resend-webhook')->name('customer-resend.webhook');

// CSRF token refresh endpoint
Route::get('/csrf-token', function () {
    return response()->json(['token' => csrf_token()]);
})->name('csrf.token');

// Sitemap
Route::get('/sitemap.xml', function () {
    $urls = [
        ['loc' => url('/'),                        'changefreq' => 'weekly',  'priority' => '1.0'],
        ['loc' => url('/terms-and-conditions'),     'changefreq' => 'monthly', 'priority' => '0.5'],
        ['loc' => url('/privacy-policy'),           'changefreq' => 'monthly', 'priority' => '0.5'],
        ['loc' => url('/refund-policy'),            'changefreq' => 'monthly', 'priority' => '0.5'],
        ['loc' => url('/auth'),                     'changefreq' => 'monthly', 'priority' => '0.8'],
    ];

    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

    foreach ($urls as $url) {
        $xml .= "  <url>\n";
        $xml .= "    <loc>{$url['loc']}</loc>\n";
        $xml .= "    <lastmod>" . now()->toDateString() . "</lastmod>\n";
        $xml .= "    <changefreq>{$url['changefreq']}</changefreq>\n";
        $xml .= "    <priority>{$url['priority']}</priority>\n";
        $xml .= "  </url>\n";
    }

    $xml .= '</urlset>';

    return response($xml, 200, ['Content-Type' => 'application/xml']);
});

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
    Route::post('/login', [AuthController::class, 'login'])->name('login')->middleware('throttle:login');
    Route::post('/signup', [AuthController::class, 'signup'])->name('signup')->middleware('throttle:auth-sensitive');
    Route::get('/google', [AuthController::class, 'redirectToGoogle'])->name('google');
    Route::get('/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('google.callback');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/check', [AuthController::class, 'checkAuth'])->name('check');
    
    // Email Verification
    Route::get('/verify-email/{token}', [AuthController::class, 'verifyEmail'])->name('verify.email');
    Route::post('/resend-verification', [AuthController::class, 'resendVerification'])->name('resend.verification')->middleware('throttle:auth-sensitive');
    
    // Password Reset
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('forgot.password');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('send.reset.link')->middleware('throttle:auth-sensitive');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('reset.password');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('reset.password.submit')->middleware('throttle:auth-sensitive');
});

// Protected Routes (require authentication)
Route::middleware(['web', 'auth'])->group(function () {

    // Account Type Selection (for Google OAuth new users)
    Route::get('/choose-account-type', [AuthController::class, 'showAccountTypeSelection'])->name('auth.choose.account.type');
    Route::post('/save-account-type', [AuthController::class, 'saveAccountType'])->name('auth.save.account.type');

    // Extension auto-login via web session
    Route::get('/extension/web-token', [ExtensionController::class, 'webAutoLogin'])->name('extension.web-token');

    // General dashboard route
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Admin "Login as user": switch back to the admin account
    Route::post('/impersonate/stop', [App\Http\Controllers\Admin\ImpersonationController::class, 'stop'])->name('impersonate.stop');
    
    // User Routes
        Route::prefix('user')->name('user.')->middleware('profile.complete')->group(function () {
            // Subscription - always accessible (so users can select/upgrade packages)
            Route::get('/subscription', [SubscriptionController::class, 'index'])->name('subscription');
            Route::post('/subscription/upgrade', [SubscriptionController::class, 'upgrade'])->name('subscription.upgrade');
            Route::post('/payment/submit', [SubscriptionController::class, 'submitPayment'])->name('payment.submit');
            Route::post('/subscription/apply-free', [SubscriptionController::class, 'applyFreePlan'])->name('subscription.apply-free');
            Route::post('/subscription/cancel', [SubscriptionController::class, 'cancelPlan'])->name('subscription.cancel');

            // Feedback - always accessible (so users can provide feedback regardless of subscription)
            Route::post('/feedback', [FeedbackController::class, 'store'])->name('feedback.store');
            Route::post('/feedback/dismiss-prompt', [FeedbackController::class, 'dismissPrompt'])->name('feedback.dismiss-prompt');
            Route::get('/feedback/history', [FeedbackController::class, 'userFeedback'])->name('feedback.history');

            // Affiliate / Referral routes (accessible without subscription)
            Route::prefix('affiliate')->name('affiliate.')->group(function () {
                Route::get('/',              [AffiliateController::class, 'index'])->name('index');
                Route::get('/referrals',     [AffiliateController::class, 'referrals'])->name('referrals');
                Route::get('/earnings',      [AffiliateController::class, 'earnings'])->name('earnings');
                Route::get('/withdrawals',   [AffiliateController::class, 'withdrawals'])->name('withdrawals');
                Route::post('/withdraw',     [AffiliateController::class, 'requestWithdrawal'])->name('withdrawal.request');
                Route::post('/generate-link',[AffiliateController::class, 'generateLink'])->name('generate-link');
            });

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
                Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('password.update')->middleware('throttle:password-change');
                Route::put('/profile/preferences', [ProfileController::class, 'updatePreferences'])->name('preferences.update');
                Route::delete('/profile/account', [ProfileController::class, 'deleteAccount'])->name('account.delete');
                Route::delete('/profile/data', [ProfileController::class, 'clearData'])->name('data.clear');
                Route::post('/profile/verify-email', [ProfileController::class, 'resendVerification'])->name('verification.send')->middleware('throttle:auth-sensitive');

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
                Route::get('/api/niches', [SearchController::class, 'getNiches'])->name('api.niches');
                Route::get('/api/place-details/{placeId}', [SearchController::class, 'getPlaceDetails'])->name('api.place-details');

                // Leads routes
                Route::get('/leads', [LeadsController::class, 'index'])->name('leads');
                Route::get('/leads/export', [LeadsController::class, 'export'])->name('leads.export');
                Route::get('/leads/export-excel', [LeadsController::class, 'exportExcel'])->name('leads.export.excel');
                Route::get('/leads/{id}', [LeadsController::class, 'show'])->name('leads.show');
                Route::post('/leads/{id}/status', [LeadsController::class, 'updateStatus'])->name('leads.status');
                Route::post('/leads/{id}/notes', [LeadsController::class, 'updateNotes'])->name('leads.notes');
                Route::post('/leads/{id}/follow-up', [LeadsController::class, 'updateFollowUp'])->name('leads.follow-up');
                Route::post('/leads/{id}/contact', [LeadsController::class, 'updateContact'])->name('leads.contact');
                Route::post('/leads/{id}/check-seo', [LeadsController::class, 'checkSeo'])->name('leads.check-seo');
                Route::delete('/leads/{id}', [LeadsController::class, 'destroy'])->name('leads.delete');
                Route::post('/leads/bulk', [LeadsController::class, 'bulkAction'])->name('leads.bulk');

                // Folder routes
                Route::get('/folders', [App\Http\Controllers\FolderController::class, 'index'])->name('folders.index');
                Route::post('/folders', [App\Http\Controllers\FolderController::class, 'store'])->name('folders.store');
                Route::post('/folders/{id}/add-leads', [App\Http\Controllers\FolderController::class, 'addLeads'])->name('folders.add-leads');
                Route::post('/folders/{id}/remove-leads', [App\Http\Controllers\FolderController::class, 'removeLeads'])->name('folders.remove-leads');
                Route::delete('/folders/{id}', [App\Http\Controllers\FolderController::class, 'destroy'])->name('folders.destroy');


                // Lead Center — business-development workspace built on top of My Leads
                Route::prefix('lead-center')->name('lead-center.')->group(function () {
                    Route::get('/', [LeadCenterController::class, 'index'])->name('index');
                    Route::post('/bulk', [LeadCenterController::class, 'bulkAction'])->name('bulk');
                    Route::post('/{id}/status', [LeadCenterController::class, 'updateStatus'])->name('status');
                    Route::post('/{id}/location', [LeadCenterController::class, 'updateLocation'])->name('location');
                    Route::delete('/{id}', [LeadCenterController::class, 'destroy'])->name('delete');

                    Route::post('/import/preview', [LeadCenterImportController::class, 'preview'])->name('import.preview');
                    Route::post('/import', [LeadCenterImportController::class, 'store'])->name('import.store');
                    Route::post('/add-from-leads', [LeadCenterImportController::class, 'addFromLeads'])->name('add-from-leads');

                    Route::get('/folders', [LeadCenterFolderController::class, 'index'])->name('folders.index');
                    Route::post('/folders', [LeadCenterFolderController::class, 'store'])->name('folders.store');
                    Route::delete('/folders/{id}', [LeadCenterFolderController::class, 'destroy'])->name('folders.destroy');

                    Route::get('/{id}/conversation', [LeadCenterConversationController::class, 'show'])->name('conversation');
                    Route::post('/{id}/conversation/messages', [LeadCenterConversationController::class, 'storeMessage'])->name('conversation.message.store');
                    Route::delete('/{id}/conversation/messages/{messageId}', [LeadCenterConversationController::class, 'destroyMessage'])->name('conversation.message.destroy');
                    Route::post('/{id}/contact-links', [LeadCenterConversationController::class, 'updateContactLinks'])->name('contact-links');

                    // Sharing: grant another registered user access to work on my Lead Center
                    Route::prefix('access')->name('access.')->group(function () {
                        Route::post('/share', [LeadCenterAccessController::class, 'share'])->name('share');
                        Route::post('/{id}/respond', [LeadCenterAccessController::class, 'respond'])->name('respond');
                        Route::post('/{id}/revoke', [LeadCenterAccessController::class, 'revoke'])->name('revoke');
                        Route::post('/switch', [LeadCenterAccessController::class, 'switchTo'])->name('switch');
                    });

                    // Outreach playbook: targeted locations, prompts, message templates
                    Route::prefix('resources')->name('resources.')->group(function () {
                        Route::get('/', [LeadCenterResourceController::class, 'index'])->name('index');
                        Route::get('/map-data', [LeadCenterResourceController::class, 'mapData'])->name('map-data');
                        Route::get('/boundary', [LeadCenterResourceController::class, 'boundary'])->name('boundary');
                        Route::post('/locations', [LeadCenterResourceController::class, 'storeLocation'])->name('locations.store');
                        Route::delete('/locations/{id}', [LeadCenterResourceController::class, 'destroyLocation'])->name('locations.destroy');
                        Route::post('/prompts', [LeadCenterResourceController::class, 'storePrompt'])->name('prompts.store');
                        Route::delete('/prompts/{id}', [LeadCenterResourceController::class, 'destroyPrompt'])->name('prompts.destroy');
                        Route::post('/templates', [LeadCenterResourceController::class, 'storeTemplate'])->name('templates.store');
                        Route::delete('/templates/{id}', [LeadCenterResourceController::class, 'destroyTemplate'])->name('templates.destroy');
                    });
                });

                // AI Sales Profile (Phase 1) — owner-scoped; team members read-only
                Route::prefix('ai-sales-profile')->name('ai-profile.')
                    ->middleware('plan.feature:ai_sales_profile_enabled')
                    ->group(function () {
                        Route::get('/', [App\Http\Controllers\AiSalesProfile\SalesProfileController::class, 'index'])->name('index');
                        Route::put('/', [App\Http\Controllers\AiSalesProfile\SalesProfileController::class, 'update'])->name('update');
                        Route::post('/offers', [App\Http\Controllers\AiSalesProfile\SalesOfferController::class, 'store'])->name('offers.store');
                        Route::put('/offers/{offer}', [App\Http\Controllers\AiSalesProfile\SalesOfferController::class, 'update'])->whereNumber('offer')->name('offers.update');
                        Route::patch('/offers/{offer}/toggle', [App\Http\Controllers\AiSalesProfile\SalesOfferController::class, 'toggle'])->whereNumber('offer')->name('offers.toggle');
                        Route::delete('/offers/{offer}', [App\Http\Controllers\AiSalesProfile\SalesOfferController::class, 'destroy'])->whereNumber('offer')->name('offers.destroy');
                    });

                // Offers in the AI Sales Profile (AI Offer Builder engine): 4 categories / 16 offer types →
                // short pre-filled form → AI offer (queued) or "Save without AI". Records are private per user.
                Route::prefix('offer-builder')->name('offer-builder.')
                    ->middleware(['plan.feature:ai_sales_profile_enabled', App\Http\Middleware\EnsureOfferBuilderInstalled::class])
                    ->group(function () {
                        $o = App\Http\Controllers\OfferBuilder\BuilderOfferController::class;

                        Route::get('/quick-form', [$o, 'quickForm'])->middleware('throttle:ai-outreach-read')->name('quick-form');
                        Route::post('/quick-save', [$o, 'quickSave'])->middleware('throttle:ai-outreach-write')->name('quick-save');
                        Route::post('/prompt', [$o, 'prompt'])->middleware('throttle:ai-outreach-read')->name('prompt');
                        Route::post('/offers', [$o, 'store'])->middleware('throttle:ai-outreach-write')->name('offers.store');
                        Route::post('/offers/{offer}/generate', [$o, 'generate'])->whereNumber('offer')->middleware('throttle:ai-outreach-write')->name('offers.generate');
                        Route::get('/offers/{offer}/generations/{generation}', [$o, 'generation'])->whereNumber(['offer', 'generation'])->middleware('throttle:ai-outreach-read')->name('offers.generation');
                        // "AI offers in progress": see what is queued/running and why, and stop it
                        Route::get('/generations', [$o, 'generations'])->middleware('throttle:ai-outreach-read')->name('generations.index');
                        Route::post('/generations/stop-all', [$o, 'stopAllGenerations'])->middleware('throttle:ai-outreach-write')->name('generations.stop-all');
                        Route::post('/generations/{generation}/stop', [$o, 'stopGeneration'])->whereNumber('generation')->middleware('throttle:ai-outreach-write')->name('generations.stop');
                    });

                // AI Outreach (Phase 2): website audit + offer match + email DRAFTS (nothing is sent)
                Route::prefix('ai-outreach')->name('ai-outreach.')
                    ->middleware('plan.feature:ai_sales_profile_enabled')
                    ->group(function () {
                        $c = App\Http\Controllers\AiOutreach\AiOutreachController::class;
                        $a = App\Http\Controllers\AiOutreach\AiAnalysisController::class;
                        $d = App\Http\Controllers\AiOutreach\AiDraftController::class;

                        Route::get('/', [$c, 'index'])->name('index');
                        Route::post('/folders/add', [$c, 'addToFolder'])->middleware('throttle:ai-outreach-write')->name('folders.add');
                        Route::get('/usage', [$c, 'usage'])->name('usage');
                        Route::post('/estimate', [$c, 'estimate'])->middleware('throttle:ai-outreach-read')->name('estimate');
                        Route::post('/analyze', [$c, 'start'])->middleware('throttle:ai-outreach-write')->name('start');
                        Route::get('/batches/{batch}', [$c, 'batch'])->whereNumber('batch')->name('batch');
                        Route::get('/batches/{batch}/status', [$c, 'batchStatus'])->whereNumber('batch')->middleware('throttle:ai-outreach-read')->name('batch.status');
                        Route::post('/batches/{batch}/retry-failed', [$c, 'retryBatchFailed'])->whereNumber('batch')->middleware('throttle:ai-outreach-write')->name('batch.retry');

                        Route::get('/analyses/{analysis}', [$a, 'show'])->whereNumber('analysis')->name('analysis');
                        Route::post('/analyses/{analysis}/retry', [$a, 'retry'])->whereNumber('analysis')->middleware('throttle:ai-outreach-write')->name('analysis.retry');
                        Route::post('/analyses/{analysis}/regenerate', [$a, 'regenerate'])->whereNumber('analysis')->middleware('throttle:ai-outreach-write')->name('analysis.regenerate');

                        Route::put('/drafts/{draft}', [$d, 'update'])->whereNumber('draft')->name('draft.update');
                        Route::post('/drafts/{draft}/approve', [$d, 'approve'])->whereNumber('draft')->name('draft.approve');
                        Route::post('/drafts/bulk-approve', [$d, 'bulkApprove'])->middleware('throttle:ai-outreach-write')->name('drafts.bulk-approve');
                        Route::post('/drafts/{draft}/discard', [$d, 'discard'])->whereNumber('draft')->name('draft.discard');
                        Route::post('/drafts/{draft}/restore', [$d, 'restore'])->whereNumber('draft')->name('draft.restore');
                    });

                // Email Sending (Phase 3): the user's own Gmail, one confirmed email at a time
                Route::prefix('email-sending')->name('email-sending.')
                    ->middleware('plan.feature:gmail_sending_enabled')
                    ->group(function () {
                        $g = App\Http\Controllers\EmailSending\GmailConnectionController::class;
                        $o = App\Http\Controllers\EmailSending\OutboundEmailController::class;

                        Route::get('/', [$g, 'index'])->name('index');
                        Route::post('/google/connect', [$g, 'connect'])->middleware('throttle:ai-outreach-write')->name('connect');
                        Route::get('/google/callback', [$g, 'callback'])->name('callback');
                        Route::post('/google/disconnect', [$g, 'disconnect'])->name('disconnect');
                        Route::post('/suppressions', [$g, 'addSuppression'])->name('suppressions.store');
                        Route::delete('/suppressions/{suppression}', [$g, 'removeSuppression'])->whereNumber('suppression')->name('suppressions.destroy');

                        Route::post('/drafts/{draft}/send', [$o, 'store'])->whereNumber('draft')->middleware('throttle:email-send')->name('send');
                        Route::post('/emails/{email}/cancel', [$o, 'cancel'])->whereNumber('email')->name('cancel');
                        Route::post('/emails/{email}/retry', [$o, 'retry'])->whereNumber('email')->middleware('throttle:email-send')->name('retry');

                        // Phase 4: link tracking preference (plan feature checked in the controller)
                        Route::put('/tracking-settings', [App\Http\Controllers\EmailTracking\TrackingSettingsController::class, 'update'])->name('tracking.update');
                    });

                // Resend (Phase 5): the customer's own Resend account (BYOK)
                Route::prefix('email-sending/resend')->name('email-sending.resend.')
                    ->middleware('plan.feature:resend_connection_enabled')
                    ->group(function () {
                        $s = App\Http\Controllers\Resend\ResendSettingsController::class;

                        Route::get('/', [$s, 'show'])->name('show');
                        Route::post('/connect', [$s, 'connect'])->middleware('throttle:ai-outreach-write')->name('connect');
                        Route::post('/check', [$s, 'check'])->middleware('throttle:ai-outreach-write')->name('check');
                        Route::post('/disconnect', [$s, 'disconnect'])->name('disconnect');
                        Route::post('/domains', [$s, 'addDomain'])->middleware('throttle:ai-outreach-write')->name('domains.store');
                        Route::post('/domains/{domain}/verify', [$s, 'verifyDomain'])->whereNumber('domain')->middleware('throttle:ai-outreach-write')->name('domains.verify');
                        Route::post('/domains/{domain}/refresh', [$s, 'refreshDomain'])->whereNumber('domain')->middleware('throttle:ai-outreach-write')->name('domains.refresh');
                        Route::put('/domains/{domain}/tracking', [$s, 'domainTracking'])->whereNumber('domain')->name('domains.tracking');
                        Route::post('/senders', [$s, 'addSender'])->name('senders.store');
                        Route::delete('/senders/{sender}', [$s, 'deleteSender'])->whereNumber('sender')->name('senders.destroy');
                        Route::post('/webhook', [$s, 'createWebhook'])->middleware('throttle:ai-outreach-write')->name('webhook.create');
                        Route::put('/webhook/secret', [$s, 'webhookSecret'])->name('webhook.secret');
                        Route::put('/lead-outreach', [$s, 'leadOutreach'])->name('lead-outreach');
                    });

                Route::post('/email-sending/resend/drafts/{draft}/send', [App\Http\Controllers\Resend\ResendSendController::class, 'store'])
                    ->whereNumber('draft')->middleware(['plan.feature:resend_single_sending_enabled', 'throttle:email-send'])->name('email-sending.resend.send');

                // Send to a lead folder: every lead gets its own AI email, spaced out (Gmail or Resend)
                $fs = App\Http\Controllers\Outreach\FolderSendController::class;
                Route::get('/campaigns/folder-send', [$fs, 'create'])->name('folder-sends.create');
                Route::post('/campaigns/folder-send', [$fs, 'store'])->middleware('throttle:email-send')->name('folder-sends.store');
                Route::get('/campaigns/folder-sends/{send}', [$fs, 'show'])->whereNumber('send')->name('folder-sends.show');
                Route::post('/campaigns/folder-sends/{send}/cancel', [$fs, 'cancel'])->whereNumber('send')->name('folder-sends.cancel');

                // Campaigns to opted-in contacts (Phase 5)
                Route::prefix('campaigns')->name('campaigns.')
                    ->middleware('plan.feature:resend_campaigns_enabled')
                    ->group(function () {
                        $c = App\Http\Controllers\Resend\CampaignController::class;
                        $k = App\Http\Controllers\Resend\ContactController::class;

                        Route::get('/', [$c, 'index'])->name('index');
                        Route::get('/create', [$c, 'create'])->name('create');
                        Route::post('/ai-write', [$c, 'aiWrite'])->middleware('throttle:ai-outreach-write')->name('ai-write');
                        Route::post('/', [$c, 'store'])->name('store');
                        Route::get('/{campaign}', [$c, 'show'])->whereNumber('campaign')->name('show');
                        Route::get('/{campaign}/edit', [$c, 'edit'])->whereNumber('campaign')->name('edit');
                        Route::put('/{campaign}', [$c, 'update'])->whereNumber('campaign')->name('update');
                        Route::post('/{campaign}/test', [$c, 'test'])->whereNumber('campaign')->middleware('throttle:email-send')->name('test');
                        Route::post('/{campaign}/approve', [$c, 'approve'])->whereNumber('campaign')->middleware('throttle:email-send')->name('approve');
                        Route::post('/{campaign}/cancel', [$c, 'cancel'])->whereNumber('campaign')->name('cancel');
                        Route::post('/templates', [$c, 'storeTemplate'])->name('templates.store');
                        Route::delete('/templates/{template}', [$c, 'destroyTemplate'])->whereNumber('template')->name('templates.destroy');

                        Route::get('/contacts', [$k, 'index'])->name('contacts.index');
                        Route::post('/contacts', [$k, 'store'])->name('contacts.store');
                        Route::post('/contacts/import', [$k, 'import'])->middleware('throttle:ai-outreach-write')->name('contacts.import');
                        Route::post('/contacts/{contact}/withdraw', [$k, 'withdraw'])->whereNumber('contact')->name('contacts.withdraw');
                        Route::get('/contacts/{contact}/history', [$k, 'history'])->whereNumber('contact')->name('contacts.history');
                        Route::post('/lists', [$k, 'storeList'])->name('lists.store');
                        Route::post('/lists/add', [$k, 'addToList'])->name('lists.add');
                    });

                // Email Analytics (Phase 4): tracking results, manual replies, timelines
                Route::prefix('email-analytics')->name('email-analytics.')
                    ->middleware('plan.feature:email_analytics_enabled')
                    ->group(function () {
                        $a = App\Http\Controllers\EmailTracking\EmailAnalyticsController::class;
                        $r = App\Http\Controllers\EmailTracking\EmailReplyController::class;

                        Route::get('/', [$a, 'index'])->name('index');
                        Route::get('/emails/{email}', [$a, 'email'])->whereNumber('email')->name('email');
                        Route::get('/leads/{lead}', [$a, 'lead'])->whereNumber('lead')->name('lead');
                        Route::post('/emails/{email}/reply', [$r, 'store'])->whereNumber('email')->name('reply.store');
                        Route::delete('/emails/{email}/reply', [$r, 'destroy'])->whereNumber('email')->name('reply.destroy');
                    });

                // Follow-up reminders (Phase 4). Reminders only: nothing is sent automatically.
                Route::prefix('email-follow-ups')->name('email-follow-ups.')
                    ->middleware('plan.feature:email_followups_enabled')
                    ->group(function () {
                        $f = App\Http\Controllers\EmailTracking\FollowUpController::class;

                        Route::get('/', [$f, 'index'])->name('index');
                        Route::post('/', [$f, 'store'])->name('store');
                        Route::post('/{followUp}/complete', [$f, 'complete'])->whereNumber('followUp')->name('complete');
                        Route::post('/{followUp}/cancel', [$f, 'cancel'])->whereNumber('followUp')->name('cancel');
                    });

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
    
    // Admin Routes — every route in this group requires an admin account (HTTP 403 otherwise)
    Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'adminDashboard'])->name('dashboard');

        // AI Setup: one active provider (Claude or ChatGPT), keys, models, effort, limits
        Route::get('/ai-setup', [App\Http\Controllers\Admin\AiSetupController::class, 'index'])->name('ai-setup.index');
        Route::put('/ai-setup', [App\Http\Controllers\Admin\AiSetupController::class, 'update'])->name('ai-setup.update');
        Route::post('/ai-setup/test/{provider}', [App\Http\Controllers\Admin\AiSetupController::class, 'test'])
            ->whereIn('provider', ['anthropic', 'openai', 'gemini', 'openrouter'])->middleware('throttle:10,1')->name('ai-setup.test');
        Route::get('/switch-to-user', [DashboardController::class, 'switchToUserView'])->name('switch.to.user');
        Route::get('/switch-to-admin', [DashboardController::class, 'switchToAdminView'])->name('switch.to.admin');
        Route::get('/users', [DashboardController::class, 'adminUsers'])->name('users');
        Route::get('/users/{user}', [DashboardController::class, 'showUser'])->name('users.show');
        Route::put('/users/{user}', [DashboardController::class, 'updateUser'])->name('users.update');
        Route::delete('/users/{user}', [DashboardController::class, 'deleteUser'])->name('users.delete');
        Route::post('/users/{user}/toggle-signups', [DashboardController::class, 'toggleSignups'])->name('users.toggle.signups');
        Route::post('/users/{user}/impersonate', [App\Http\Controllers\Admin\ImpersonationController::class, 'start'])->name('users.impersonate');
        Route::post('/users/{user}/password', [App\Http\Controllers\Admin\ImpersonationController::class, 'password'])->middleware('throttle:30,1')->name('users.password');

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
        Route::get('/subscriptions/{subscription}/devices', [AdminSubscriptionController::class, 'devices'])->name('subscriptions.devices');
        Route::get('/subscriptions/{subscription}/features', [AdminSubscriptionController::class, 'features'])->name('subscriptions.features');
        Route::put('/subscriptions/{subscription}/features', [AdminSubscriptionController::class, 'updateFeatures'])->name('subscriptions.features.update');
        Route::delete('/devices/{device}', [AdminSubscriptionController::class, 'destroyDevice'])->name('devices.destroy');

        // Payments
        Route::get('/payments', [AdminPaymentController::class, 'index'])->name('payments.index');
        Route::post('/payments/{payment}/status', [AdminPaymentController::class, 'updateStatus'])->name('payments.update-status');
        Route::get('/payments/{payment}/screenshot', [AdminPaymentController::class, 'screenshot'])->name('payments.screenshot');

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

        // API Usage Management
        Route::prefix('api-usage')->name('api-usage.')->group(function () {
            Route::get('/', [ApiUsageController::class, 'index'])->name('index');
            Route::post('/', [ApiUsageController::class, 'store'])->name('store');
            Route::get('/{apiKey}/edit', [ApiUsageController::class, 'edit'])->name('edit');
            Route::put('/{apiKey}', [ApiUsageController::class, 'update'])->name('update');
            Route::delete('/{apiKey}', [ApiUsageController::class, 'destroy'])->name('destroy');
            Route::post('/{apiKey}/toggle-status', [ApiUsageController::class, 'toggleStatus'])->name('toggle-status');
            Route::post('/{apiKey}/reset-counts', [ApiUsageController::class, 'resetCounts'])->name('reset-counts');
        });

        // Feedback Management
        Route::prefix('feedback')->name('feedback.')->group(function () {
            Route::get('/history', [FeedbackController::class, 'adminIndex'])->name('history');
            Route::post('/{feedback}/status', [FeedbackController::class, 'updateStatus'])->name('update-status');
        });

        // Affiliate Management
        Route::prefix('affiliate')->name('affiliate.')->middleware(\App\Http\Middleware\AdminMiddleware::class)->group(function () {
            Route::get('/',                                       [AdminAffiliateController::class, 'index'])->name('index');
            Route::get('/affiliates',                             [AdminAffiliateController::class, 'affiliates'])->name('affiliates');
            Route::get('/affiliates/{user}',                      [AdminAffiliateController::class, 'show'])->name('show');
            Route::post('/affiliates/{user}/toggle',              [AdminAffiliateController::class, 'toggleStatus'])->name('toggle');
            Route::put('/affiliates/{user}/commission',           [AdminAffiliateController::class, 'updateCommission'])->name('commission');
            Route::get('/withdrawals',                            [AdminAffiliateController::class, 'withdrawals'])->name('withdrawals');
            Route::post('/withdrawals/{withdrawal}/process',      [AdminAffiliateController::class, 'processWithdrawal'])->name('withdrawal.process');
            Route::get('/conversions',                            [AdminAffiliateController::class, 'conversions'])->name('conversions');
            Route::post('/conversions/{conversion}/approve',      [AdminAffiliateController::class, 'approveConversion'])->name('conversion.approve');
            Route::post('/conversions/{conversion}/reject',       [AdminAffiliateController::class, 'rejectConversion'])->name('conversion.reject');
            Route::get('/settings',                               [AdminAffiliateController::class, 'settings'])->name('settings');
            Route::put('/settings',                               [AdminAffiliateController::class, 'updateSettings'])->name('settings.update');
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
            Route::post('/migrations/run-specific', [App\Http\Controllers\Admin\SettingsController::class, 'runSpecificMigration'])->name('migrations.run-specific');

            // Composer Commands
            Route::post('/composer/run', [App\Http\Controllers\Admin\SettingsController::class, 'runComposerCommand'])->name('composer.run');
        });
    });
});