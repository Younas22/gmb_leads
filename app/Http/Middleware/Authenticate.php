<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    protected function redirectTo(Request $request): ?string
    {
        \Log::info('Custom Authenticate middleware called');
        return $request->expectsJson() ? null : route('auth.show');
    }

}
