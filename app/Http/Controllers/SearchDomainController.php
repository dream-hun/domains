<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\DomainType;
use App\Helpers\DomainSearchHelper;
use App\Http\Requests\SearchDomainRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class SearchDomainController extends Controller
{
    public function __construct(private readonly DomainSearchHelper $domainSearchHelper) {}

    public function index(): View
    {
        return view('domains.search');
    }

    public function search(SearchDomainRequest $request): View|RedirectResponse
    {
        $validated = $request->validated();
        $domain = $validated['domain'] ?? null;
        $domainType = DomainType::from($validated['domain_type']);

        if (! $domain) {
            return $this->redirectWithError('Please enter a domain to search.');
        }
        $result = $this->domainSearchHelper->processDomainSearch($domain, $domainType);

        if (isset($result['error']) && ! isset($result['details']) && empty($result['suggestions'])) {
            return $this->redirectWithError($result['error']);
        }

        $viewData = [
            'details' => $result['details'] ?? null,
            'suggestions' => $result['suggestions'] ?? [],
            'domainType' => $result['domainType'] ?? null,
            'searchedDomain' => $result['searchedDomain'] ?? $domain,
            'hasServiceErrors' => $this->hasServiceErrors($result),
            'errorMessage' => $result['error'] ?? null,
        ];

        return view('domains.search', $viewData);
    }

    /**
     * Redirect back with an error message.
     */
    private function redirectWithError(string $message): RedirectResponse
    {
        return back()
            ->withInput()
            ->with('error', $message);
    }

    private function hasServiceErrors(array $result): bool
    {

        if (isset($result['details']['service_error']) && $result['details']['service_error']) {
            return true;
        }
        if (isset($result['suggestions'])) {
            foreach ($result['suggestions'] as $suggestion) {
                if (isset($suggestion['service_error']) && $suggestion['service_error']) {
                    return true;
                }
            }
        }

        return false;
    }
}
