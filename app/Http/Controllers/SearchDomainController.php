<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\DomainType;
use App\Helpers\DomainSearchHelper;
use App\Http\Requests\SearchDomainRequest;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

final class SearchDomainController extends Controller
{
    public function __construct(private readonly DomainSearchHelper $domainSearchHelper) {}

    public function index(): View
    {
        return view('domains.search', [
            'popularDomains' => $this->getPopularDomainsForDisplay(),
        ]);
    }

    public function search(SearchDomainRequest $request): View|JsonResponse
    {
        $validated = $request->validated();
        $domain = mb_trim($validated['domain'] ?? '');

        if ($domain === '' || $domain === '0') {
            return $this->handleError('Please enter a domain name to search.', $request);
        }

        if (mb_strlen($domain) < 2) {
            return $this->handleError('Domain name must be at least 2 characters long.', $request);
        }

        if (mb_strlen($domain) > 253) {
            return $this->handleError('Domain name is too long. Maximum length is 253 characters.', $request);
        }

        // Additional validation using helper
        if (! $this->domainSearchHelper->isValidDomainName($domain)) {
            return $this->handleError('Invalid domain name format. Please use only letters, numbers, dots, and hyphens.', $request);
        }

        try {
            $result = $this->domainSearchHelper->processDomainSearch($domain);

            if (isset($result['error']) && ! isset($result['details']) && empty($result['suggestions'])) {
                return $this->handleError($result['error'], $request);
            }

            $responseData = [
                'details' => $result['details'] ?? null,
                'suggestions' => $result['suggestions'] ?? [],
                'domainType' => $result['domainType'] ?? null,
                'searchedDomain' => $result['searchedDomain'] ?? $domain,
                'hasServiceErrors' => $this->hasServiceErrors($result),
                'errorMessage' => $result['error'] ?? null,
                'searchPerformed' => true,
                'popularDomains' => $this->getPopularDomainsForDisplay(),
            ];

            Log::info('Domain search completed', [
                'domain' => $domain,
                'domainType' => $result['domainType'] !== null ? $result['domainType']->value : 'unknown',
                'hasResults' => ! empty($result['details']) || ! empty($result['suggestions']),
                'hasErrors' => ! empty($result['error']),
                'isAjax' => $request->ajax(),
            ]);

            // Return JSON for AJAX requests
            if ($request->ajax()) {
                return response()->json($responseData);
            }

            return view('domains.search', $responseData);

        } catch (Exception $exception) {
            Log::error('Domain search controller error', [
                'domain' => $domain,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'isAjax' => $request->ajax(),
            ]);

            return $this->handleError('An unexpected error occurred while searching. Please try again.', $request);
        }
    }

    private function handleError(string $message, Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            return response()->json(['error' => $message], 400);
        }

        $data = [
            'errorMessage' => $message,
            'searchPerformed' => true,
            'popularDomains' => $this->getPopularDomainsForDisplay(),
            'details' => null,
            'suggestions' => [],
            'domainType' => null,
            'searchedDomain' => $request->input('domain'),
        ];

        if (! $request->ajax()) {
            session()->flash('error', $message);

            return view('domains.search', $data);
        }

        return response()->json(['error' => $message], 400);
    }

    /**
     * Check if any service errors occurred in the search results
     */
    private function hasServiceErrors(array $result): bool
    {
        // Check primary domain result for service errors
        if (isset($result['details']['service_error']) && $result['details']['service_error']) {
            return true;
        }

        // Check suggestions for service errors
        if (isset($result['suggestions']) && is_array($result['suggestions'])) {
            foreach ($result['suggestions'] as $suggestion) {
                if (isset($suggestion['service_error']) && $suggestion['service_error']) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get popular domains for display in the UI
     */
    private function getPopularDomainsForDisplay(): array
    {
        $popularDomains = [
            'local' => [],
            'international' => [],
        ];

        try {
            // Get popular local domains
            $popularDomains['local'] = $this->domainSearchHelper->getPopularDomains(DomainType::Local, 3);

            // Get popular international domains
            $popularDomains['international'] = $this->domainSearchHelper->getPopularDomains(DomainType::International);

        } catch (Exception $exception) {
            Log::warning('Failed to load popular domains', [
                'error' => $exception->getMessage(),
            ]);

            // Fallback to default popular domains
            $popularDomains = [
                'local' => [
                    ['tld' => '.rw', 'price' => '15,000 RWF', 'currency' => 'RWF'],
                ],
                'international' => [
                    ['tld' => '.com', 'price' => '$12.99', 'currency' => 'USD'],
                    ['tld' => '.net', 'price' => '$14.99', 'currency' => 'USD'],
                    ['tld' => '.org', 'price' => '$13.99', 'currency' => 'USD'],
                    ['tld' => '.info', 'price' => '$22.99', 'currency' => 'USD'],
                    ['tld' => '.xyz', 'price' => '$8.99', 'currency' => 'USD'],
                ],
            ];
        }

        return $popularDomains;
    }
}
