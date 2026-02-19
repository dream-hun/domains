<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class SearchDomainController extends Controller
{
    public function index(Request $request): View
    {
        return view('domains.search', [
            'domain' => $request->query('domain'),
        ]);
    }

    /**
     * Redirect POST form submissions (e.g. from home page) to GET /domains with domain param.
     * The Livewire DomainSearchPage component runs search on mount when domain is present.
     */
    public function search(Request $request): RedirectResponse
    {
        $domain = mb_trim((string) $request->input('domain', ''));

        return to_route('domains', $domain !== '' ? ['domain' => $domain] : []);
    }
}
