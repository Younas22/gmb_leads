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

        // URL mein ref hai aur abhi tak track nahi hua
        if ($refCode) {
            $existing = $request->cookie('ref_code') ?? $request->session()->get('ref_code');

            // Last-click attribution: naya ref code always override karo
            if (!$existing || $existing !== $refCode) {
                AffiliateService::trackClick($refCode, $request);
            }

            // Session mein store karo (most reliable)
            $request->session()->put('ref_code', $refCode);
            $request->session()->save();

            $response = $next($request);

            // Cookie bhi set karo as backup
            return $response->withCookie(AffiliateService::setCookie($refCode));
        }

        return $next($request);
    }
}
