<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Enums\DomainType;
use App\Models\DomainPrice;
use App\Services\Domain\EppDomainService;
use App\Services\Domain\NamecheapDomainService;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;

final readonly class DomainSearchHelper
{
    use \App\Traits\HasCurrency;

    public function __construct(
        private NamecheapDomainService $internationalDomainService,
        private EppDomainService $eppDomainService
    ) {}

    public function processDomainSearch(string $domain): array
    {
        try {
            $sanitizedDomain = $this->sanitizeDomain($domain);
            $domainParts = explode('.', $sanitizedDomain);
            $domainBase = $domainParts[0];
            $searchedTld = count($domainParts) > 1 ? end($domainParts) : null;

            $error = null;

            // Auto-detect domain type based on TLD or use default
            $domainType = $this->detectDomainType($sanitizedDomain);

            $primaryDomainToSearch = $sanitizedDomain;
            if (! $searchedTld) {
                $defaultTld = ($domainType === DomainType::Local) ? '.rw' : '.com';
                $primaryDomainToSearch .= $defaultTld;
                $domainType = $this->detectDomainType($primaryDomainToSearch);
            }

            // Execute the search using the appropriate service based on detected type
            if ($domainType === DomainType::Local) {
                [$details, $suggestions] = $this->searchLocalDomains($primaryDomainToSearch, $domainBase);
            } else {
                [$details, $suggestions] = $this->searchInternationalDomains($primaryDomainToSearch, $domainBase);
            }

            if (! $details && empty($suggestions)) {
                $error = 'Unable to check domain availability. Please try again later.';
            }

            return [
                'details' => $details,
                'suggestions' => array_values($suggestions),
                'domainType' => $domainType,
                'searchedDomain' => $sanitizedDomain,
                'error' => $error,
            ];
        } catch (ConnectionException $e) {
            Log::error('Domain search connection error', ['domain' => $domain, 'error' => $e->getMessage()]);

            return ['error' => 'Connection error. Please check your internet connection and try again.'];
        } catch (Exception $e) {
            Log::error('Domain search unexpected error', ['domain' => $domain, 'error' => $e->getMessage()]);

            return ['error' => 'An unexpected error occurred. Our team has been notified.'];
        }
    }

    /**
     * Validate domain name format
     */
    public function isValidDomainName(string $domain): bool
    {
        $sanitized = $this->sanitizeDomain($domain);

        // Basic domain validation
        if ($sanitized === '' || $sanitized === '0' || mb_strlen($sanitized) < 2 || mb_strlen($sanitized) > 253) {
            return false;
        }

        // Check for valid characters (letters, numbers, dots, hyphens)
        if (in_array(preg_match('/^[a-zA-Z0-9.-]+$/', $sanitized), [0, false], true)) {
            return false;
        }

        // Check domain structure
        $parts = explode('.', $sanitized);

        foreach ($parts as $part) {
            if ($part === '' || $part === '0' || mb_strlen($part) > 63) {
                return false;
            }

            // Cannot start or end with hyphen
            if (str_starts_with($part, '-') || str_ends_with($part, '-')) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get popular domains for display
     */
    public function getPopularDomains(DomainType $type, int $limit = 5, ?string $targetCurrency = null): array
    {
        $targetCurrency = $targetCurrency ?? $this->getUserCurrency()->code;

        return DomainPrice::where('type', $type)
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn ($price): array => [
                'tld' => $price->tld,
                'price' => $price->getFormattedPrice('register_price', $targetCurrency),
                'currency' => $targetCurrency,
                'base_currency' => $price->getBaseCurrency(),
            ])
            ->toArray();
    }

    /**
     * Handles searching for local domains and suggestions.
     *
     * @throws Exception
     */
    private function searchLocalDomains(string $primaryDomain, string $domainBase): array
    {
        $details = null;
        $suggestions = [];
        $primaryTld = explode('.', $primaryDomain)[1] ?? null;

        if ($primaryTld !== null && $primaryTld !== '' && $primaryTld !== '0') {
            $primaryResult = $this->eppDomainService->searchDomains($domainBase, [$primaryTld]);
            if (isset($primaryResult[$primaryDomain])) {
                $result = $primaryResult[$primaryDomain];
                $priceInfo = $this->findDomainPriceInfo($primaryTld);
                $targetCurrency = $this->getUserCurrency()->code;

                $details = [
                    'domain' => $primaryDomain,
                    'available' => $this->normalizeAvailabilityStatus($result['available'] ?? false),
                    'price' => $priceInfo?->getFormattedPrice('register_price', $targetCurrency) ?? $result['price'],
                    'service_error' => false,
                    'error_message' => $result['reason'] ?? null,
                    'type' => DomainType::Local->value,
                    'currency' => $targetCurrency,
                    'base_currency' => $priceInfo?->getBaseCurrency() ?? 'RWF',
                ];
            }
        }

        $allLocalTlds = DomainPrice::where('type', DomainType::Local)->pluck('tld')->map(fn ($tld): string => mb_ltrim($tld, '.'))->toArray();
        $suggestionTlds = array_diff($allLocalTlds, [$primaryTld]);

        if ($suggestionTlds !== []) {
            $suggestionResults = $this->eppDomainService->searchDomains($domainBase, $suggestionTlds);
            unset($suggestionResults[$primaryDomain]);

            // Normalize the suggestion results
            foreach ($suggestionResults as $domainName => $result) {
                $tld = explode('.', $domainName)[1] ?? null;
                $priceInfo = $this->findDomainPriceInfo($tld);
                $targetCurrency = $this->getUserCurrency()->code;

                $suggestions[$domainName] = [
                    'domain' => $domainName,
                    'available' => $this->normalizeAvailabilityStatus($result['available'] ?? false),
                    'price' => $priceInfo?->getFormattedPrice('register_price', $targetCurrency) ?? $result['price'],
                    'service_error' => false,
                    'error_message' => $result['reason'] ?? null,
                    'type' => DomainType::Local->value,
                    'currency' => $targetCurrency,
                    'base_currency' => $priceInfo?->getBaseCurrency() ?? 'RWF',
                ];
            }
        }

        return [$details, $suggestions];
    }

    private function searchInternationalDomains(string $primaryDomain, string $domainBase): array
    {
        $details = null;
        $suggestions = [];
        $primaryTld = explode('.', $primaryDomain)[1] ?? null;

        try {
            if ($primaryTld !== null && $primaryTld !== '' && $primaryTld !== '0') {
                $availabilityResults = $this->internationalDomainService->checkAvailability([$primaryDomain]);
                if (isset($availabilityResults[$primaryDomain])) {
                    $result = $availabilityResults[$primaryDomain];
                    $priceInfo = $this->findDomainPriceInfo($primaryTld);

                    // Debug log the raw result
                    Log::debug('International domain check result', [
                        'domain' => $primaryDomain,
                        'result' => (array) $result,
                        'available_property' => $result->available ?? 'not_set',
                        'error_property' => $result->error ?? 'no_error',
                    ]);

                    $targetCurrency = $this->getUserCurrency()->code;

                    $details = [
                        'domain' => $primaryDomain,
                        'available' => $this->normalizeAvailabilityStatus($result->available ?? false),
                        'price' => $priceInfo?->getFormattedPrice('register_price', $targetCurrency),
                        'service_error' => ! empty($result->error),
                        'error_message' => $result->error ?? null,
                        'type' => DomainType::International->value,
                        'currency' => $targetCurrency,
                        'base_currency' => $priceInfo?->getBaseCurrency() ?? 'USD',
                    ];
                }
            }

            $suggestionTlds = DomainPrice::where('type', DomainType::International)
                ->where('tld', '!=', '.'.$primaryTld)
                ->latest()
                ->limit(10)
                ->pluck('tld')->map(fn ($tld): string => mb_ltrim($tld, '.'))
                ->toArray();

            $domainsToSuggest = array_map(fn ($tld): string => $domainBase.'.'.$tld, $suggestionTlds);

            if ($domainsToSuggest !== []) {
                $suggestionResults = $this->internationalDomainService->checkAvailability($domainsToSuggest);
                foreach ($suggestionResults as $domainName => $result) {
                    if ($domainName === $primaryDomain) {
                        continue;
                    }
                    $tld = explode('.', $domainName)[1];
                    $priceInfo = $this->findDomainPriceInfo($tld);

                    Log::debug('International suggestion result', [
                        'domain' => $domainName,
                        'available' => $result->available ?? 'not_set',
                        'error' => $result->error ?? 'no_error',
                    ]);

                    $targetCurrency = $this->getUserCurrency()->code;

                    $suggestions[$domainName] = [
                        'domain' => $domainName,
                        'available' => $this->normalizeAvailabilityStatus($result->available ?? false),
                        'price' => $priceInfo?->getFormattedPrice('register_price', $targetCurrency),
                        'service_error' => ! empty($result->error),
                        'error_message' => $result->error ?? null,
                        'type' => DomainType::International->value,
                        'currency' => $targetCurrency,
                        'base_currency' => $priceInfo?->getBaseCurrency() ?? 'USD',
                    ];
                }
            }
        } catch (Exception $e) {
            Log::error('International domain search error', ['domain' => $primaryDomain, 'error' => $e->getMessage()]);
            if ($details !== null && $details !== []) {
                $details['service_error'] = true;
                $details['error_message'] = 'Could not fetch suggestions due to a service error.';
            }
        }

        return [$details, $suggestions];
    }

    private function sanitizeDomain(string $domain): string
    {
        $domain = mb_trim(mb_strtolower($domain));
        $domain = preg_replace('/^https?:\/\//', '', $domain);
        $domain = preg_replace('/^www\./', '', $domain);

        return mb_trim($domain, '/.');
    }

    private function findDomainPriceInfo(string $tld): ?DomainPrice
    {
        $cleanTld = mb_ltrim($tld, '.');

        return DomainPrice::where('tld', '.'.$cleanTld)->first();
    }

    private function normalizeAvailabilityStatus($available): string
    {
        if (is_bool($available)) {
            return $available ? 'true' : 'false';
        }
        if (is_string($available)) {
            return in_array(mb_strtolower($available), ['true', '1', 'yes', 'available'], true) ? 'true' : 'false';
        }

        return 'false';
    }

    /**
     * Auto-detect domain type based on TLD
     */
    private function detectDomainType(string $domain): DomainType
    {
        $domainParts = explode('.', $domain);
        $tld = count($domainParts) > 1 ? end($domainParts) : null;

        if ($tld === 'rw') {
            return DomainType::Local;
        }

        return DomainType::International;
    }
}
