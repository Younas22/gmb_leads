<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ExtensionController;

/*
|--------------------------------------------------------------------------
| Extension API Routes
| Base URL: /api/extension/...
|--------------------------------------------------------------------------
| Rate limiters are defined in App\Providers\AppServiceProvider. A 429 response
| carries both "error" and "message" keys, matching what the extension reads.
*/

Route::prefix('extension')->group(function () {

    // Public - no token needed
    Route::post('/login',  [ExtensionController::class, 'login'])->middleware('throttle:login');

    // Authenticated - token required (Bearer)
    Route::middleware('throttle:extension-api')->group(function () {
        Route::post('/logout',                      [ExtensionController::class, 'logout']);
        Route::get('/status',                       [ExtensionController::class, 'status']);
        Route::post('/register-device',             [ExtensionController::class, 'registerDevice']);
        Route::get('/devices',                      [ExtensionController::class, 'listDevices']);
        Route::delete('/devices/{id}',              [ExtensionController::class, 'removeDevice']);
        Route::post('/save-leads',                  [ExtensionController::class, 'saveLeads']);
    });

});
