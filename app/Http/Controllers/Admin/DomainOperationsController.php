<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Domain\GetDomainContactStatsAction;
use App\Actions\Domain\SyncDomainContactsAction;
use App\Actions\Domain\UpdateDomainContactsAction;
use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Services\Domain\NamecheapDomainService;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final class DomainOperationsController extends Controller
{
    public function __construct(
        private readonly NamecheapDomainService $domainService,
        private readonly SyncDomainContactsAction $syncContactsAction,
        private readonly UpdateDomainContactsAction $updateContactsAction,
        private readonly GetDomainContactStatsAction $contactStatsAction
    ) {}

    /**
     * Show domain information
     */
    public function domainInfo(Domain $domain): View|Factory
    {
        $domain = Domain::query()->findOrFail($domain->uuid);

        return view('admin.domainOps.info', ['domain' => $domain]);
    }

    /**
     * Sync domain contacts from registry to local database
     */
    public function getContacts(Domain $domain): RedirectResponse
    {
        $result = $this->syncContactsAction->execute($domain);

        if ($result['success']) {
            return redirect()->back()->with('success', $result['message']);
        }

        return redirect()->back()->with('error', $result['message']);
    }

    /**
     * Update domain contacts in the registry
     */
    public function updateContacts(Domain $domain): RedirectResponse
    {
        $result = $this->updateContactsAction->execute($domain);

        if ($result['success']) {
            return redirect()->back()->with('success', $result['message']);
        }

        return redirect()->back()->with('error', $result['message']);
    }

    /**
     * Get contact statistics for a domain
     */
    public function getContactStats(Domain $domain): array
    {
        return $this->contactStatsAction->execute($domain);
    }
}
