<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Enums\TldType;
use App\Models\Tld;
use App\Services\Domain\EppDomainService;
use App\Services\Domain\NamecheapDomainService;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final readonly class DomainSearchHelper
{
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

            $domainType = $this->detectDomainType($sanitizedDomain);

            $primaryDomainToSearch = $sanitizedDomain;
            if (! $searchedTld) {
                $defaultTld = ($domainType === TldType::Local) ? '.rw' : '.com';
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
            if ($domainType === TldType::Local) {
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
     * Get popular domains for display. Prices are shown in the currency they are stored in (no conversion).
     */
    public function getPopularDomains(TldType $type, int $limit = 5, ?string $targetCurrency = null): array
    {
        $query = Tld::query()
            ->with(['tldPricings' => fn ($q) => $q->current()->with('currency')])
            ->latest();
        if ($type === TldType::Local) {
            $query->localTlds();
        } else {
            $query->internationalTlds();
        }

        $preferredCurrency = $targetCurrency ?? CurrencyHelper::getUserCurrency();

        return $query
            ->limit($limit)
            ->get()
            ->map(function (Tld $price) use ($preferredCurrency): array {
                $display = $price->getDisplayPriceForCurrency($preferredCurrency, 'register_price');

                return [
                    'tld' => $price->tld,
                    'price' => $price->getFormattedPriceWithFallback('register_price', $preferredCurrency),
                    'currency' => $display['currency_code'],
                    'base_currency' => $price->getBaseCurrency(),
                ];
            })
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
        $primaryDomainParts = explode('.', $primaryDomain);
        $primaryTld = count($primaryDomainParts) > 1 ? implode('.', array_slice($primaryDomainParts, 1)) : null;

        $allLocalTlds = Tld::query()->localTlds()->pluck('name')->map(fn (string $name): string => mb_ltrim($name, '.'))->all();
        $suggestionTlds = array_diff($allLocalTlds, [$primaryTld]);
        $tldsToLoad = array_filter(array_merge([$primaryTld], $suggestionTlds), fn ($t): bool => ! in_array($t, [null, '', '0'], true));
        $priceInfoMap = $this->findDomainPriceInfoBatch(array_values($tldsToLoad));

        if (! in_array($primaryTld, [null, '', '0'], true)) {
            $primaryResult = $this->eppDomainService->searchDomains($domainBase, [$primaryTld]);
            if (isset($primaryResult[$primaryDomain])) {
                $result = $primaryResult[$primaryDomain];
                $priceInfo = $priceInfoMap[mb_ltrim($primaryTld, '.')] ?? null;
                $preferredCurrency = CurrencyHelper::getUserCurrency();
                $available = $result['available'];
                $reason = $result['reason'] ?? null;

                $details = [
                    'domain' => $primaryDomain,
                    'available' => $this->normalizeAvailabilityStatus($available),
                    'price' => $priceInfo?->getFormattedPriceWithFallback('register_price', $preferredCurrency) ?? $result['price'],
                    'service_error' => false,
                    'error_message' => $reason !== '' ? $reason : null,
                    'type' => TldType::Local->value,
                    'currency' => $priceInfo instanceof Tld ? $priceInfo->getDisplayPriceForCurrency($preferredCurrency, 'register_price')['currency_code'] : 'RWF',
                    'base_currency' => $priceInfo?->getBaseCurrency() ?? 'RWF',
                    'tld_id' => $priceInfo instanceof Tld ? $priceInfo->id : null,
                ];
            }
        }

        if ($suggestionTlds !== []) {
            $suggestionResults = $this->eppDomainService->searchDomains($domainBase, $suggestionTlds);
            unset($suggestionResults[$primaryDomain]);

            $preferredCurrency = CurrencyHelper::getUserCurrency();
            foreach ($suggestionResults as $domainName => $result) {
                $tldParts = explode('.', (string) $domainName);
                $tld = count($tldParts) > 1 ? implode('.', array_slice($tldParts, 1)) : null;
                $priceInfo = $tld !== null ? ($priceInfoMap[mb_ltrim($tld, '.')] ?? null) : null;
                $available = $result['available'];
                $reason = $result['reason'] ?? null;

                $suggestions[$domainName] = [
                    'domain' => $domainName,
                    'available' => $this->normalizeAvailabilityStatus($available),
                    'price' => $priceInfo?->getFormattedPriceWithFallback('register_price', $preferredCurrency) ?? $result['price'],
                    'service_error' => false,
                    'error_message' => $reason !== '' ? $reason : null,
                    'type' => TldType::Local->value,
                    'currency' => $priceInfo instanceof Tld ? $priceInfo->getDisplayPriceForCurrency($preferredCurrency, 'register_price')['currency_code'] : 'RWF',
                    'base_currency' => $priceInfo?->getBaseCurrency() ?? 'RWF',
                    'tld_id' => $priceInfo instanceof Tld ? $priceInfo->id : null,
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

        $suggestionTlds = Tld::query()->internationalTlds()
            ->whereRaw($this->tldNormalizedNotEqualRaw(), [mb_strtolower((string) $primaryTld)])
            ->latest()
            ->limit(10)
            ->pluck('name')->map(fn (string $name): string => mb_ltrim($name, '.'))
            ->all();

        $tldsToLoad = array_values(array_filter(array_merge([$primaryTld], $suggestionTlds), fn ($t): bool => ! in_array($t, [null, '', '0'], true)));
        $priceInfoMap = $this->findDomainPriceInfoBatch($tldsToLoad);

        try {
            if (! in_array($primaryTld, [null, '', '0'], true)) {
                $availabilityResults = $this->internationalDomainService->checkAvailability([$primaryDomain]);
                if (isset($availabilityResults[$primaryDomain])) {
                    $result = $availabilityResults[$primaryDomain];
                    $priceInfo = $priceInfoMap[mb_ltrim($primaryTld, '.')] ?? null;
                    $preferredCurrency = CurrencyHelper::getUserCurrency();

                    // Debug log the raw result
                    Log::debug('International domain check result', [
                        'domain' => $primaryDomain,
                        'result' => (array) $result,
                        'available_property' => $result['available'],
                        'error_property' => $result['reason'],
                    ]);

                    $details = [
                        'domain' => $primaryDomain,
                        'available' => $this->normalizeAvailabilityStatus($result['available']),
                        'price' => $priceInfo?->getFormattedPriceWithFallback('register_price', $preferredCurrency),
                        'service_error' => $result['available'] === false && $result['reason'] !== '',
                        'error_message' => $result['reason'] !== '' ? $result['reason'] : null,
                        'type' => TldType::International->value,
                        'currency' => $priceInfo instanceof Tld ? $priceInfo->getDisplayPriceForCurrency($preferredCurrency, 'register_price')['currency_code'] : 'USD',
                        'base_currency' => $priceInfo?->getBaseCurrency() ?? 'USD',
                        'tld_id' => $priceInfo instanceof Tld ? $priceInfo->id : null,
                    ];
                }
            }

            $domainsToSuggest = array_map(fn (string $tld): string => $domainBase.'.'.$tld, $suggestionTlds);

            if ($domainsToSuggest !== []) {
                $suggestionResults = $this->internationalDomainService->checkAvailability($domainsToSuggest);
                foreach ($suggestionResults as $domainName => $result) {
                    if ($domainName === $primaryDomain) {
                        continue;
                    }

                    $tld = explode('.', $domainName)[1];
                    $priceInfo = $priceInfoMap[mb_ltrim($tld, '.')] ?? null;
                    $preferredCurrency = CurrencyHelper::getUserCurrency();

                    Log::debug('International suggestion result', [
                        'domain' => $domainName,
                        'available' => $result['available'],
                        'error' => $result['reason'],
                    ]);

                    $suggestions[$domainName] = [
                        'domain' => $domainName,
                        'available' => $this->normalizeAvailabilityStatus($result['available']),
                        'price' => $priceInfo?->getFormattedPriceWithFallback('register_price', $preferredCurrency),
                        'service_error' => $result['available'] === false && $result['reason'] !== '',
                        'error_message' => $result['reason'] !== '' ? $result['reason'] : null,
                        'type' => TldType::International->value,
                        'currency' => $priceInfo instanceof Tld ? $priceInfo->getDisplayPriceForCurrency($preferredCurrency, 'register_price')['currency_code'] : 'USD',
                        'base_currency' => $priceInfo?->getBaseCurrency() ?? 'USD',
                        'tld_id' => $priceInfo instanceof Tld ? $priceInfo->id : null,
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

    private function simulateTestingResponse(string $primaryDomain, TldType $domainType, string $domainBase, string $searchedDomain): array
    {
        $tld = $this->extractTldSegment($primaryDomain);
        $priceInfo = $tld ? $this->findDomainPriceInfo($tld) : null;
        $preferredCurrency = CurrencyHelper::getUserCurrency();
        $displayCurrency = $priceInfo instanceof Tld ? $priceInfo->getDisplayPriceForCurrency($preferredCurrency, 'register_price')['currency_code'] : 'USD';

        $details = [
            'domain' => $primaryDomain,
            'available' => true,
            'price' => $priceInfo?->getFormattedPriceWithFallback('register_price', $preferredCurrency),
            'service_error' => false,
            'error_message' => null,
            'type' => $domainType->value,
            'currency' => $displayCurrency,
            'base_currency' => $priceInfo?->getBaseCurrency() ?? 'USD',
            'tld_id' => $priceInfo instanceof Tld ? $priceInfo->id : null,
        ];

        $suggestions = Tld::query()
            ->with(['tldPricings' => fn ($q) => $q->current()->with('currency')])
            ->when($domainType === TldType::Local, fn ($q): mixed => $q->localTlds(), fn ($q): mixed => $q->internationalTlds())
            ->when($tld, fn ($query): mixed => $query->whereRaw($this->tldNormalizedNotEqualRaw(), [mb_strtolower((string) $tld)]))
            ->limit(5)
            ->get()
            ->map(function (Tld $price) use ($domainBase, $domainType, $preferredCurrency): array {
                $suggestionTld = mb_ltrim($price->tld, '.');
                $display = $price->getDisplayPriceForCurrency($preferredCurrency, 'register_price');

                return [
                    'domain' => $domainBase.'.'.$suggestionTld,
                    'available' => true,
                    'price' => $price->getFormattedPriceWithFallback('register_price', $preferredCurrency),
                    'service_error' => false,
                    'error_message' => null,
                    'type' => $domainType->value,
                    'currency' => $display['currency_code'],
                    'base_currency' => $price->getBaseCurrency(),
                    'tld_id' => $price->id,
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

    /**
     * Load multiple TLDs with pricing and currency in one query to avoid N+1.
     *
     * @param  array<int, string>  $tlds
     * @return array<string, Tld> map of normalized tld (e.g. 'com') => Tld
     */
    private function findDomainPriceInfoBatch(array $tlds): array
    {
        if ($tlds === []) {
            return [];
        }

        $normalized = array_unique(array_map(fn (string $t): string => mb_ltrim($t, '.'), $tlds));
        $names = array_map(fn (string $t): string => '.'.$t, $normalized);

        $loaded = Tld::query()
            ->with(['tldPricings' => fn ($q) => $q->current()->with('currency')])
            ->whereIn('name', $names)
            ->get();

        $map = [];
        foreach ($loaded as $tld) {
            $key = mb_ltrim($tld->name, '.');
            $map[$key] = $tld;
        }

        return $map;
    }

    private function findDomainPriceInfo(string $tld): ?Tld
    {
        $cleanTld = mb_ltrim($tld, '.');

        return Tld::query()
            ->with(['tldPricings' => fn ($q) => $q->current()->with('currency')])
            ->where('name', '.'.$cleanTld)
            ->first();
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
    private function detectDomainType(string $domain): TldType
    {
        $domainParts = explode('.', $domain);
        $tld = count($domainParts) > 1 ? end($domainParts) : null;

        if ($tld === 'rw') {
            return TldType::Local;
        }

        return TldType::International;
    }

    private function tldNormalizedNotEqualRaw(): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? "LOWER(LTRIM(name, '.')) != ?"
            : "LOWER(TRIM(LEADING '.' FROM name)) != ?";
    }
}
