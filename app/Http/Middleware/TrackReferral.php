<?php

namespace App\Http\Middleware;

use App\Services\AffiliateService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackReferral
{
    public function handle(Request $request, Closure $next): Response
    {
        $refCode = $request->query('ref');

        if ($refCode && !$request->cookie('ref_code')) {
            AffiliateService::trackClick($refCode, $request);

            $response = $next($request);
            return $response->withCookie(AffiliateService::setCookie($refCode));
        }

        return $next($request);
    }
}
