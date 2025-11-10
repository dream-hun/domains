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
        private readonly int $timeout = 30,
        private readonly int $extendedTimeout = 45
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
     * Fetch pair conversion rate between two currencies
     *
     * @return array<string, mixed>|null Returns full API response or null on failure
     */
    public function fetchPairConversion(string $from, string $to, ?float $amount = null): ?array
    {
        try {
            $endpoint = sprintf('%s/%s/pair/%s/%s', $this->baseUrl, $this->apiKey, $from, $to);

            if ($amount !== null) {
                $endpoint .= '/'.$amount;
            }

            $response = Http::timeout($this->timeout)
                ->retry(2, 1000)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'User-Agent' => config('app.name', 'Laravel').'/1.0',
                ])
                ->get($endpoint);

            if (! $response->successful()) {
                Log::warning('Exchange rate pair conversion API request failed', [
                    'from' => $from,
                    'to' => $to,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $data = $response->json();

            // Check for API errors
            if (isset($data['result']) && $data['result'] === 'error') {
                Log::error('Exchange rate API returned error', [
                    'from' => $from,
                    'to' => $to,
                    'error_type' => $data['error-type'] ?? 'unknown',
                ]);

                return $data; // Return error response so caller can handle it
            }

            return $data;

        } catch (Exception $exception) {
            Log::error('Exchange rate pair conversion exception', [
                'from' => $from,
                'to' => $to,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
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
                ->get(sprintf('%s/%s/latest/%s', $this->baseUrl, $this->apiKey, $baseCurrencyCode));

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

        } catch (Exception $exception) {
            Log::warning('Exchange rate API exception', [
                'error' => $exception->getMessage(),
                'timeout' => $timeout,
                'retries' => $retries,
            ]);

            return null;
        }
    }
}
