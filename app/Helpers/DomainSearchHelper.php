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
    public function __construct(
        private NamecheapDomainService $internationalDomainService,
        private EppDomainService $eppDomainService
    ) {}

    public function processDomainSearch(string $domain, DomainType $domainType): array
    {
        try {
            $sanitizedDomain = $this->sanitizeDomain($domain);
            $domainParts = explode('.', $sanitizedDomain);
            $domainBase = $domainParts[0];
            $searchedTld = count($domainParts) > 1 ? end($domainParts) : null;

            $error = null;

            $primaryDomainToSearch = $sanitizedDomain;
            if (! $searchedTld) {
                $defaultTld = ($domainType === DomainType::Local) ? '.rw' : '.com';
                $primaryDomainToSearch .= $defaultTld;
            }

            // Execute the search using the appropriate service.
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
                $details = $primaryResult[$primaryDomain];
            }
        }

        $allLocalTlds = DomainPrice::where('type', DomainType::Local)->pluck('tld')->map(fn ($tld): string => ltrim($tld, '.'))->toArray();
        $suggestionTlds = array_diff($allLocalTlds, [$primaryTld]);

        if ($suggestionTlds !== []) {
            $suggestionResults = $this->eppDomainService->searchDomains($domainBase, $suggestionTlds);
            unset($suggestionResults[$primaryDomain]);
            $suggestions = $suggestionResults;
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
                    $details = [
                        'domain' => $primaryDomain,
                        'available' => $this->normalizeAvailabilityStatus($result->available ?? false),
                        'price' => $priceInfo?->getFormattedPrice(),
                        'service_error' => ! empty($result->error),
                        'error_message' => $result->error,
                        'type' => DomainType::International->value,
                    ];
                }
            }

            $suggestionTlds = DomainPrice::where('type', DomainType::International)
                ->where('tld', '!=', '.'.$primaryTld)
                ->latest()
                ->limit(10)
                ->pluck('tld')->map(fn ($tld): string => ltrim($tld, '.'))
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
                    $suggestions[$domainName] = [
                        'domain' => $domainName,
                        'available' => $this->normalizeAvailabilityStatus($result->available ?? false),
                        'price' => $priceInfo?->getFormattedPrice(),
                        'service_error' => ! empty($result->error),
                        'error_message' => $result->error,
                        'type' => DomainType::International->value,
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
        $domain = trim(mb_strtolower($domain));
        $domain = preg_replace('/^https?:\/\//', '', $domain);
        $domain = preg_replace('/^www\./', '', $domain);

        return trim($domain, '/.');
    }

    private function findDomainPriceInfo(string $tld): ?DomainPrice
    {
        $cleanTld = ltrim($tld, '.');

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
}
