<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class CurrencyHelper
{
    /**
     * Get currency info based on visitor's IP.
     * Pakistan visitors get PKR, everyone else gets USD.
     */
    public static function getVisitorCurrency(?string $ip = null): array
    {
        $ip = $ip ?: request()->ip();

        // Default USD
        $default = [
            'code'   => 'USD',
            'symbol' => '$',
            'rate'   => 1,
        ];

        // Only public IPs can be geolocated; skip lookups for localhost / private / invalid addresses.
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return $default;
        }

        try {
            // Check country from IP (cache per IP for 1 hour). TLS is verified; short timeouts so a
            // slow geo service cannot stall the page.
            $countryCode = Cache::remember("geo_country_{$ip}", 3600, function () use ($ip) {
                $response = Http::timeout(3)->connectTimeout(2)
                    ->get('https://reallyfreegeoip.org/json/' . rawurlencode($ip));
                if (!$response->successful()) return null;
                return $response->json('country_code');
            });

            if ($countryCode !== 'PK') {
                return $default;
            }

            // Get USD to PKR rate (cache for 6 hours)
            $unirateKey = config('services.unirate.key');
            if (!$unirateKey) {
                return $default;
            }

            $pkrRate = Cache::remember('usd_to_pkr_rate', 21600, function () use ($unirateKey) {
                $response = Http::timeout(5)->connectTimeout(3)->get('https://api.unirateapi.com/api/rates', [
                    'api_key' => $unirateKey,
                    'from'    => 'USD',
                ]);
                if (!$response->successful()) return null;
                return $response->json('rates.PKR');
            });

            if (!$pkrRate) {
                return $default;
            }

            return [
                'code'   => 'PKR',
                'symbol' => 'Rs',
                'rate'   => (float) $pkrRate,
            ];
        } catch (\Exception $e) {
            return $default;
        }
    }

    /**
     * Convert a USD price to the visitor's currency.
     */
    public static function convert(float $usdPrice, array $currency): float
    {
        return round($usdPrice * $currency['rate'], 0);
    }
}
