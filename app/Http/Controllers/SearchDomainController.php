<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\DomainType;
use App\Helpers\DomainSearchHelper;
use App\Http\Requests\SearchDomainRequest;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
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

    public function search(SearchDomainRequest $request): View|RedirectResponse
    {
        $validated = $request->validated();
        $domain = mb_trim($validated['domain'] ?? '');

        // Enhanced domain validation
        if ($domain === '' || $domain === '0') {
            return $this->redirectWithError('Please enter a domain name to search.');
        }

        if (mb_strlen($domain) < 2) {
            return $this->redirectWithError('Domain name must be at least 2 characters long.');
        }

        if (mb_strlen($domain) > 253) {
            return $this->redirectWithError('Domain name is too long. Maximum length is 253 characters.');
        }

        // Additional validation using helper
        if (! $this->domainSearchHelper->isValidDomainName($domain)) {
            return $this->redirectWithError('Invalid domain name format. Please use only letters, numbers, dots, and hyphens.');
        }

        // Perform the domain search (domain type is auto-detected)
        try {
            $result = $this->domainSearchHelper->processDomainSearch($domain);

            // If there's a critical error with no results, redirect with error
            if (isset($result['error']) && ! isset($result['details']) && empty($result['suggestions'])) {
                return $this->redirectWithError($result['error']);
            }

            // Prepare view data
            $viewData = [
                'details' => $result['details'] ?? null,
                'suggestions' => $result['suggestions'] ?? [],
                'domainType' => $result['domainType'] ?? null,
                'searchedDomain' => $result['searchedDomain'] ?? $domain,
                'hasServiceErrors' => $this->hasServiceErrors($result),
                'errorMessage' => $result['error'] ?? null,
                'searchPerformed' => true,
                'popularDomains' => $this->getPopularDomainsForDisplay(),
            ];

            // Log successful searches for analytics
            Log::info('Domain search completed', [
                'domain' => $domain,
                'domainType' => $result['domainType']?->value ?? 'unknown',
                'hasResults' => ! empty($result['details']) || ! empty($result['suggestions']),
                'hasErrors' => ! empty($result['error']),
            ]);

            return view('domains.search', $viewData);

        } catch (Exception $e) {
            Log::error('Domain search controller error', [
                'domain' => $domain,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->redirectWithError('An unexpected error occurred while searching. Please try again.');
        }
    }

    /**
     * Get domain suggestions via AJAX (for future implementation)
     */
    public function getSuggestions(SearchDomainRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $domain = mb_trim($validated['domain'] ?? '');

        if ($domain === '' || $domain === '0' || mb_strlen($domain) < 2) {
            return response()->json([
                'success' => false,
                'message' => 'Please enter a valid domain name.',
            ]);
        }

        try {
            $result = $this->domainSearchHelper->processDomainSearch($domain);

            return response()->json([
                'success' => true,
                'details' => $result['details'] ?? null,
                'suggestions' => $result['suggestions'] ?? [],
                'hasServiceErrors' => $this->hasServiceErrors($result),
                'errorMessage' => $result['error'] ?? null,
            ]);

        } catch (Exception $e) {
            Log::error('AJAX domain search error', [
                'domain' => $domain,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while searching for domains.',
            ]);
        }
    }

    /**
     * Redirect back with an error message and maintain form state
     */
    private function redirectWithError(string $message): RedirectResponse
    {
        return back()
            ->withInput()
            ->with('error', $message);
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

        } catch (Exception $e) {
            Log::warning('Failed to load popular domains', [
                'error' => $e->getMessage(),
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
