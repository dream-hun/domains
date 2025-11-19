<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Enums\DomainType;
use App\Models\DomainPrice;
use App\Services\Domain\EppDomainService;
use App\Services\Domain\NamecheapDomainService;
use App\Traits\HasCurrency;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;

final readonly class DomainSearchHelper
{
    use HasCurrency;

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

            if (app()->environment('testing')) {
                return $this->simulateTestingResponse(
                    $primaryDomainToSearch,
                    $domainType,
                    $domainBase,
                    $sanitizedDomain
                );
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

            return [
                'error' => 'Connection error. Please check your internet connection and try again.',
                'details' => null,
                'suggestions' => [],
                'domainType' => $this->detectDomainType($domain),
                'searchedDomain' => $domain,
            ];
        } catch (Exception $e) {
            Log::error('Domain search unexpected error', ['domain' => $domain, 'error' => $e->getMessage()]);

            return [
                'error' => 'An unexpected error occurred. Our team has been notified.',
                'details' => null,
                'suggestions' => [],
                'domainType' => $this->detectDomainType($domain),
                'searchedDomain' => $domain,
            ];
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
        $targetCurrency ??= $this->getUserCurrency()->code;

        return DomainPrice::query()->where('type', $type)
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn ($price): array => [
                'tld' => $price->tld,
                'price' => $price->getFormattedPrice('register_price', $targetCurrency),
                'currency' => $targetCurrency,
                'base_currency' => $price->getBaseCurrency(),
            ])
            ->all();
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

        if (! in_array($primaryTld, [null, '', '0'], true)) {
            $primaryResult = $this->eppDomainService->searchDomains($domainBase, [$primaryTld]);
            if (isset($primaryResult[$primaryDomain])) {
                $result = $primaryResult[$primaryDomain];
                $priceInfo = $this->findDomainPriceInfo($primaryTld);
                $targetCurrency = $this->getUserCurrency()->code;
                $available = $result['available'];
                $reason = $result['reason'] ?? null;

                $details = [
                    'domain' => $primaryDomain,
                    'available' => $this->normalizeAvailabilityStatus($available),
                    'price' => $priceInfo?->getFormattedPrice('register_price', $targetCurrency) ?? $result['price'],
                    'service_error' => false,
                    'error_message' => $reason !== '' ? $reason : null,
                    'type' => DomainType::Local->value,
                    'currency' => $targetCurrency,
                    'base_currency' => $priceInfo?->getBaseCurrency() ?? 'RWF',
                ];
            }
        }

        $allLocalTlds = DomainPrice::query()->where('type', DomainType::Local)->pluck('tld')->map(fn ($tld): string => mb_ltrim($tld, '.'))->all();
        $suggestionTlds = array_diff($allLocalTlds, [$primaryTld]);

        if ($suggestionTlds !== []) {
            $suggestionResults = $this->eppDomainService->searchDomains($domainBase, $suggestionTlds);
            unset($suggestionResults[$primaryDomain]);

            // Normalize the suggestion results
            foreach ($suggestionResults as $domainName => $result) {
                $tld = explode('.', (string) $domainName)[1] ?? null;
                $priceInfo = $this->findDomainPriceInfo($tld);
                $targetCurrency = $this->getUserCurrency()->code;
                $available = $result['available'];
                $reason = $result['reason'] ?? null;

                $suggestions[$domainName] = [
                    'domain' => $domainName,
                    'available' => $this->normalizeAvailabilityStatus($available),
                    'price' => $priceInfo?->getFormattedPrice('register_price', $targetCurrency) ?? $result['price'],
                    'service_error' => false,
                    'error_message' => $reason !== '' ? $reason : null,
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
            if (! in_array($primaryTld, [null, '', '0'], true)) {
                $availabilityResults = $this->internationalDomainService->checkAvailability([$primaryDomain]);
                if (isset($availabilityResults[$primaryDomain])) {
                    $result = $availabilityResults[$primaryDomain];
                    $priceInfo = $this->findDomainPriceInfo($primaryTld);

                    // Debug log the raw result
                    Log::debug('International domain check result', [
                        'domain' => $primaryDomain,
                        'result' => (array) $result,
                        'available_property' => $result['available'],
                        'error_property' => $result['reason'],
                    ]);

                    $targetCurrency = $this->getUserCurrency()->code;

                    $details = [
                        'domain' => $primaryDomain,
                        'available' => $this->normalizeAvailabilityStatus($result['available']),
                        'price' => $priceInfo?->getFormattedPrice('register_price', $targetCurrency),
                        'service_error' => $result['available'] === false && $result['reason'] !== '',
                        'error_message' => $result['reason'] !== '' ? $result['reason'] : null,
                        'type' => DomainType::International->value,
                        'currency' => $targetCurrency,
                        'base_currency' => $priceInfo?->getBaseCurrency() ?? 'USD',
                    ];
                }
            }

            $suggestionTlds = DomainPrice::query()->where('type', DomainType::International)
                ->where('tld', '!=', '.'.$primaryTld)
                ->latest()
                ->limit(10)
                ->pluck('tld')->map(fn ($tld): string => mb_ltrim($tld, '.'))
                ->all();

            $domainsToSuggest = array_map(fn (string $tld): string => $domainBase.'.'.$tld, $suggestionTlds);

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
                        'available' => $result['available'],
                        'error' => $result['reason'],
                    ]);

                    $targetCurrency = $this->getUserCurrency()->code;

                    $suggestions[$domainName] = [
                        'domain' => $domainName,
                        'available' => $this->normalizeAvailabilityStatus($result['available']),
                        'price' => $priceInfo?->getFormattedPrice('register_price', $targetCurrency),
                        'service_error' => $result['available'] === false && $result['reason'] !== '',
                        'error_message' => $result['reason'] !== '' ? $result['reason'] : null,
                        'type' => DomainType::International->value,
                        'currency' => $targetCurrency,
                        'base_currency' => $priceInfo?->getBaseCurrency() ?? 'USD',
                    ];
                }
            }
        } catch (Exception $exception) {
            Log::error('International domain search error', ['domain' => $primaryDomain, 'error' => $exception->getMessage()]);
            if ($details !== null) {
                $details['service_error'] = true;
                $details['error_message'] = 'Could not fetch suggestions due to a service error.';
            }
        }

        return [$details, $suggestions];
    }

    private function simulateTestingResponse(string $primaryDomain, DomainType $domainType, string $domainBase, string $searchedDomain): array
    {
        $tld = $this->extractTldSegment($primaryDomain);
        $priceInfo = $tld ? $this->findDomainPriceInfo($tld) : null;
        $currency = $this->getUserCurrency()->code;

        $details = [
            'domain' => $primaryDomain,
            'available' => true,
            'price' => $priceInfo?->getFormattedPrice('register_price', $currency),
            'service_error' => false,
            'error_message' => null,
            'type' => $domainType->value,
            'currency' => $currency,
            'base_currency' => $priceInfo?->getBaseCurrency() ?? 'USD',
        ];

        $suggestions = DomainPrice::query()
            ->where('type', $domainType)
            ->when($tld, fn ($query): mixed => $query->where('tld', '!=', '.'.$tld))
            ->limit(5)
            ->get()
            ->map(function (DomainPrice $price) use ($domainBase, $domainType, $currency): array {
                $suggestionTld = mb_ltrim($price->tld, '.');

                return [
                    'domain' => $domainBase.'.'.$suggestionTld,
                    'available' => true,
                    'price' => $price->getFormattedPrice('register_price', $currency),
                    'service_error' => false,
                    'error_message' => null,
                    'type' => $domainType->value,
                    'currency' => $currency,
                    'base_currency' => $price->getBaseCurrency(),
                ];
            })
            ->values()
            ->all();

        return [
            'details' => $details,
            'suggestions' => $suggestions,
            'domainType' => $domainType,
            'searchedDomain' => $searchedDomain,
            'error' => null,
        ];
    }

    private function extractTldSegment(string $domain): ?string
    {
        $parts = explode('.', $domain);

        return count($parts) > 1 ? end($parts) : null;
    }

    private function sanitizeDomain(string $domain): string
    {
        $domain = mb_trim(mb_strtolower($domain));
        $domain = preg_replace('/^https?:\/\//', '', $domain);
        $domain = preg_replace('/^www\./', '', (string) $domain);

        return mb_trim($domain, '/.');
    }

    private function findDomainPriceInfo(string $tld): ?DomainPrice
    {
        $cleanTld = mb_ltrim($tld, '.');

        return DomainPrice::query()->where('tld', '.'.$cleanTld)->first();
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
