<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

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

        try {
            // Check country from IP (cache per IP for 1 hour)
            $countryCode = Cache::remember("geo_country_{$ip}", 3600, function () use ($ip) {
                $response = @file_get_contents("https://reallyfreegeoip.org/json/{$ip}");
                if (!$response) return null;
                $data = json_decode($response, true);
                return $data['country_code'] ?? null;
            });

            if ($countryCode !== 'PK') {
                return $default;
            }

            // Get USD to PKR rate (cache for 6 hours)
            $pkrRate = Cache::remember('usd_to_pkr_rate', 21600, function () {
                $response = @file_get_contents(
                    'https://api.unirateapi.com/api/rates?api_key=wcA8sm0ovjTgpE2hXi2KB8dtkmjLcGvDlscMZcJ7ZhN60myszpZIe0Pdgdmi6blq&from=USD'
                );
                if (!$response) return null;
                $data = json_decode($response, true);
                return $data['rates']['PKR'] ?? null;
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
