<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Domain\GetDomainContactStatsAction;
use App\Actions\Domain\SyncDomainContactsAction;
use App\Actions\Domain\UpdateDomainContactsAction;
use App\Http\Controllers\Controller;
use App\Models\Domain;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final class DomainOperationsController extends Controller
{
    public function __construct(
        private readonly SyncDomainContactsAction $syncContactsAction,
        private readonly UpdateDomainContactsAction $updateContactsAction,
        private readonly GetDomainContactStatsAction $contactStatsAction
    ) {}

    /**
     * Show domain information
     */
    public function domainInfo(Domain $domain): View|Factory
    {
        if ($domain->owner_id !== auth()->id() && ! auth()->user()->isAdmin()) {
            return redirect()->back()->with('error', 'You are not authorized to view this domain.');
        }
        $domain->load(['contacts' => function ($query): void {
            $query->withPivot('type', 'user_id')->withoutGlobalScopes();
        }]);

        return view('admin.domainOps.info', ['domain' => $domain]);
    }

    /**
     * Sync domain contacts from registry to local database
     */
    public function getContacts(Domain $domain): RedirectResponse
    {
        if ($domain->owner_id !== auth()->id() && ! auth()->user()->isAdmin()) {
            return redirect()->back()->with('error', 'You are not authorized to sync contacts for this domain.');
        }

        $result = $this->syncContactsAction->execute($domain);

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    /**
     * Update domain contacts in the registry
     */
    public function updateContacts(Domain $domain): RedirectResponse
    {
        if ($domain->owner_id !== auth()->id() && ! auth()->user()->isAdmin()) {
            return redirect()->back()->with('error', 'You are not authorized to update contacts for this domain.');
        }

        $result = $this->updateContactsAction->execute($domain);

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    /**
     * Get contact statistics for a domain
     */
    public function getContactStats(Domain $domain): array
    {
        return $this->contactStatsAction->execute($domain);
    }
}
