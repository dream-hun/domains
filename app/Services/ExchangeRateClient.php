<?php

declare(strict_types=1);

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class ExchangeRateClient
{
    public function __construct(
        private ?string $apiKey = null,
        private ?string $baseUrl = null,
        private int $timeout = 30,
        private int $extendedTimeout = 45
    ) {
        $this->apiKey = $apiKey ?? config('services.exchange_rate.api_key');
        $this->baseUrl = $baseUrl ?? config('services.exchange_rate.base_url');
    }

    /**
     * Fetch exchange rates for a base currency
     *
     * @return array<string, float>|null
     */
    public function fetchRates(string $baseCurrencyCode): ?array
    {
        // Try with standard timeout first
        $rates = $this->attemptFetch($baseCurrencyCode, $this->timeout, 2);

        if ($rates !== null) {
            return $rates;
        }

        // Retry with extended timeout
        Log::warning('Standard timeout failed, attempting with extended timeout');

        return $this->attemptFetch($baseCurrencyCode, $this->extendedTimeout, 3);
    }

    /**
     * Attempt to fetch rates with specified timeout and retry count
     *
     * @return array<string, float>|null
     */
    private function attemptFetch(string $baseCurrencyCode, int $timeout, int $retries): ?array
    {
        try {
            $response = Http::timeout($timeout)
                ->retry($retries, 1000)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'User-Agent' => config('app.name', 'Laravel').'/1.0',
                ])
                ->get("{$this->baseUrl}/{$this->apiKey}/latest/{$baseCurrencyCode}");

            if (! $response->successful()) {
                Log::warning('Exchange rate API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $data = $response->json();

            // Support both 'rates' (v6 API) and 'conversion_rates' (v7 API) formats
            $rates = $data['rates'] ?? $data['conversion_rates'] ?? null;

            if (! is_array($rates)) {
                Log::error('Invalid API response structure - missing rates', [
                    'available_keys' => array_keys($data),
                    'response_sample' => json_encode(array_slice($data, 0, 3)),
                ]);

                return null;
            }

            return $rates;

        } catch (Exception $e) {
            Log::warning('Exchange rate API exception', [
                'error' => $e->getMessage(),
                'timeout' => $timeout,
                'retries' => $retries,
            ]);

            return null;
        }
    }
}
