<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Domains\GetDomainInfoAction;
use App\Actions\Domains\ListDomainAction;
use App\Actions\Domains\ReactivateDomainAction;
use App\Actions\Domains\RenewDomainAction;
use App\Actions\Domains\ToggleDomainLockAction;
use App\Actions\Domains\TransferDomainAction;
use App\Actions\Domains\UpdateDomainContactsAction;
use App\Actions\Domains\UpdateNameserversAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DomainRenewalRequest;
use App\Http\Requests\Admin\DomainTransferRequest;
use App\Http\Requests\Admin\ReactivateDomainRequest;
use App\Http\Requests\Admin\UpdateDomainContactsRequest;
use App\Http\Requests\Admin\UpdateNameserversRequest;
use App\Models\Contact;
use App\Models\Country;
use App\Models\Domain;
use App\Models\DomainPrice;
use Exception;
use Gate;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

final class DomainController extends Controller
{
    public function index(ListDomainAction $action): View|Factory
    {
        abort_if(Gate::denies('domain_access'), 403);
        $domains = $action->handle();

        return view('admin.domains.index', ['domains' => $domains]);
    }

    public function domainInfo(Domain $domain, GetDomainInfoAction $action): View
    {
        abort_if(Gate::denies('domain_show'), 403);

        try {

            $registrarInfo = $action->handle($domain);
            $domain->load(['nameservers']);
            $domain->load(['contacts' => function ($query): void {
                $query->withPivot('type', 'user_id')->withoutGlobalScopes();
            }]);

            return view('admin.domains.domainInfo', [
                'domainInfo' => $domain,
                'registrarInfo' => $registrarInfo['success'] ? $registrarInfo : null,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to get domain info: '.$e->getMessage());

            return view('admin.domains.domainInfo', [
                'domainInfo' => $domain,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function showTransferForm(Domain $domain): View
    {
        abort_if(Gate::denies('domain_transfer'), 403);

        $contacts = Contact::where('user_id', auth()->id())->get();

        return view('admin.domains.transfer', [
            'domain' => $domain,
            'contacts' => $contacts,
        ]);
    }

    public function transferDomain(Domain $domain, DomainTransferRequest $request, TransferDomainAction $action): RedirectResponse
    {
        $result = $action->handle($domain, $request->validated());

        if ($result['success']) {
            return redirect()->route('admin.domains.index')
                ->with('success', $result['message'] ?? 'Domain transfer initiated successfully');
        }

        return redirect()->back()
            ->withErrors(['error' => $result['message'] ?? 'Domain transferRegister failed'])
            ->withInput();
    }

    public function showRenewForm(Domain $domain): View
    {
        abort_if(Gate::denies('domain_renew'), 403);
        $tld = $this->extractTld($domain->name);
        $domainPrice = DomainPrice::where('tld', $tld)->first();

        $pricing = [];
        if ($domainPrice) {
            for ($i = 1; $i <= 10; $i++) {
                $pricing[$i] = ($domainPrice->renewal_price ?? $domainPrice->register_price) * $i / 100;
            }
        }

        return view('admin.domains.renew', [
            'domain' => $domain,
            'pricing' => $pricing,
        ]);
    }

    public function renewDomain(Domain $domain, DomainRenewalRequest $request, RenewDomainAction $action): RedirectResponse
    {
        $years = (int) $request->validated()['years'];
        $result = $action->handle($domain, $years);

        if ($result['success']) {
            return redirect()->route('admin.domains.index')
                ->with('success', $result['message'] ?? 'Domain renewed successfully');
        }

        return redirect()->back()
            ->withErrors(['error' => $result['message'] ?? 'Domain renewal failed'])
            ->withInput();
    }

    public function edit(Domain $domain): View
    {
        abort_if(Gate::denies('domain_edit'), 403);
        $countries = Country::select('name', 'iso_code')->get();
        $domain->load(['owner', 'nameservers']);
        $domain->load(['contacts' => function ($query): void {
            $query->withPivot('type', 'user_id')->withoutGlobalScopes();
        }]);
        $user = auth()->user();
        if ($user->isAdmin()) {
            $availableContacts = Contact::withoutGlobalScopes()->get();
        } else {
            $availableContacts = Contact::withoutGlobalScopes()
                ->where(function ($query) use ($user, $domain) {
                    $query->where('user_id', $user->id)
                        ->orWhereHas('domains', function ($q) use ($domain) {
                            $q->where('domains.id', $domain->id);
                        })
                        ->orWhereNull('user_id');
                })->get();
        }

        $contactsByType = [
            'registrant' => null,
            'admin' => null,
            'tech' => null,
            'technical' => null,
            'billing' => null,
            'auxbilling' => null,
        ];

        foreach ($domain->contacts as $contact) {
            $contactType = $contact->pivot->type;
            $contactsByType[$contactType] = $contact;
            $contact->type = $contactType;
        }

        return view('admin.domains.nameservers', [
            'domain' => $domain,
            'countries' => $countries,
            'availableContacts' => $availableContacts,
            'contactsByType' => $contactsByType,
        ]);
    }

    public function updateNameservers(Domain $domain, UpdateNameserversRequest $request, UpdateNameserversAction $action): RedirectResponse
    {
        $result = $action->handle($domain, $request->validated()['nameservers']);

        if ($result['success']) {
            return redirect()->route('admin.domains.index')
                ->with('success', $result['message'] ?? 'Nameservers updated successfully');
        }

        return redirect()->back()
            ->withErrors(['error' => $result['message'] ?? 'Nameserver update failed'])
            ->withInput();
    }

    public function toggleLock(Domain $domain, ToggleDomainLockAction $action): RedirectResponse
    {
        abort_if(Gate::denies('domain_edit'), 403);
        $lock = ! $domain->is_locked;
        $result = $action->execute($domain, $lock);

        if ($result['success']) {
            return redirect()->back()->with('success', $result['message']);
        }

        return redirect()->back()->withErrors(['error' => $result['message'] ?? 'Failed to update domain lock status']);
    }


    public function editContact(Domain $domain, string $type): View
    {
        abort_if(Gate::denies('domain_edit'), 403);
        $validTypes = ['registrant', 'admin', 'technical', 'billing'];
        if (! in_array($type, $validTypes)) {
            abort(404, 'Invalid contact type');
        }
        $domain->load(['contacts' => function ($query) {
            $query->withPivot('type', 'user_id')->withoutGlobalScopes();
        }]);
        $user = auth()->user();
        if ($user->isAdmin()) {
            $availableContacts = Contact::withoutGlobalScopes()->get();
        } else {
            $availableContacts = Contact::withoutGlobalScopes()
                ->where(function ($query) use ($user, $domain) {
                    $query->where('user_id', $user->id)
                        ->orWhereHas('domains', function ($q) use ($domain) {
                            $q->where('domains.id', $domain->id);
                        })
                        ->orWhereNull('user_id');
                })->get();
        }
        $currentContact = null;
        foreach ($domain->contacts as $contact) {
            $contactType = $contact->pivot->type;
            if ($contactType === $type ||
                ($type === 'technical' && $contactType === 'tech') ||
                ($type === 'billing' && $contactType === 'auxbilling')) {
                $currentContact = $contact;
                break;
            }
        }

        $countries = Country::select('name', 'iso_code')->get();

        return view('admin.domains.contacts.edit', [
            'domain' => $domain,
            'contactType' => $type,
            'availableContacts' => $availableContacts,
            'currentContact' => $currentContact,
            'countries' => $countries,
        ]);
    }

    public function updateContacts(Domain $domain, UpdateDomainContactsRequest $request, UpdateDomainContactsAction $action): RedirectResponse
    {
        abort_if(Gate::denies('domain_edit') || $domain->owner_id !== auth()->id(), 403);

        $result = $action->handle($domain, $request->validated());

        if ($result['success']) {
            return redirect()->route('admin.domains.edit', $domain->uuid)
                ->with('success', 'Domain contacts updated successfully');
        }

        return redirect()->back()
            ->withErrors(['error' => $result['message'] ?? 'Failed to update domain contacts']);
    }

    public function reactivate(ReactivateDomainRequest $request, ReactivateDomainAction $action): RedirectResponse
    {
        try {
            $domain = Domain::where('name', $request->validated('domain'))->firstOrFail();

            $result = $action->handle($domain);

            if (! $result['success']) {
                return back()->withErrors(['error' => $result['message'] ?? 'Failed to reactivate domain']);
            }

            return back()->with('success', $result['message'] ?? 'Domain reactivated successfully');

        } catch (Exception $e) {
            Log::error('Domain reactivation controller error', [
                'domain' => $request->validated('domain'),
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['error' => 'Failed to reactivate domain: '.$e->getMessage()]);
        }
    }

    private function extractTld(string $domain): string
    {
        $parts = explode('.', $domain);

        return '.'.end($parts);
    }
}
