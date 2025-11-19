<?php

declare(strict_types=1);

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Stevebauman\Location\Facades\Location;

final class GeolocationService
{
    private const CACHE_TTL = 3600; // 1 hour

    private const RWANDA_COUNTRY_CODE = 'RW';

    /**
     * Get user's country code based on IP address
     */
    public function getUserCountryCode(?string $ip = null): ?string
    {
        // Use provided IP or get from request
        if (! $ip) {
            $ip = request()->ip();
        }

        // Return null for local/private IPs
        if (! $this->isValidPublicIp($ip)) {
            Log::debug('Invalid or local IP address', ['ip' => $ip]);

            // In local development, check for a fallback country in environment
            if (app()->environment('local', 'testing')) {
                $fallbackCountry = config('app.local_default_country');
                if ($fallbackCountry) {
                    Log::info('Using fallback country for local development', ['country' => $fallbackCountry]);

                    return $fallbackCountry;
                }
            }

            return null;
        }

        // Cache the country code per IP for 1 hour
        $cacheKey = 'user_country_'.$ip;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($ip): ?string {
            try {
                $location = Location::get($ip);

                if ($location && $location->countryCode) {
                    Log::debug('Country detected', [
                        'ip' => $ip,
                        'country_code' => $location->countryCode,
                        'country_name' => $location->countryName,
                    ]);

                    return $location->countryCode;
                }

                Log::warning('No country code found for IP', ['ip' => $ip]);

                return null;
            } catch (Exception $exception) {
                Log::error('Error detecting country', [
                    'ip' => $ip,
                    'error' => $exception->getMessage(),
                ]);

                return null;
            }
        });
    }

    /**
     * Check if user is from Rwanda
     */
    public function isUserFromRwanda(?string $ip = null): bool
    {
        $countryCode = $this->getUserCountryCode($ip);

        return $countryCode === self::RWANDA_COUNTRY_CODE;
    }

    /**
     * Check if IP address is valid and public
     */
    private function isValidPublicIp(string $ip): bool
    {
        // Filter out local/private IPs
        if (
            in_array($ip, ['127.0.0.1', 'localhost', '::1'], true) ||
            str_starts_with($ip, '192.168.') ||
            str_starts_with($ip, '10.') ||
            str_starts_with($ip, '172.16.') ||
            str_starts_with($ip, '172.17.') ||
            str_starts_with($ip, '172.18.') ||
            str_starts_with($ip, '172.19.') ||
            str_starts_with($ip, '172.20.') ||
            str_starts_with($ip, '172.21.') ||
            str_starts_with($ip, '172.22.') ||
            str_starts_with($ip, '172.23.') ||
            str_starts_with($ip, '172.24.') ||
            str_starts_with($ip, '172.25.') ||
            str_starts_with($ip, '172.26.') ||
            str_starts_with($ip, '172.27.') ||
            str_starts_with($ip, '172.28.') ||
            str_starts_with($ip, '172.29.') ||
            str_starts_with($ip, '172.30.') ||
            str_starts_with($ip, '172.31.')
        ) {
            return false;
        }

        // Filter invalid format
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
    }
}
