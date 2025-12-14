<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Domains\GetDomainInfoAction;
use App\Actions\Domains\ListDomainAction;
use App\Actions\Domains\ReactivateDomainAction;
use App\Actions\Domains\ToggleDomainLockAction;
use App\Actions\Domains\TransferDomainAction;
use App\Actions\Domains\UpdateDomainContactsAction;
use App\Actions\Domains\UpdateNameserversAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignDomainOwnerRequest;
use App\Http\Requests\Admin\DomainTransferRequest;
use App\Http\Requests\Admin\ReactivateDomainRequest;
use App\Http\Requests\Admin\ToggleDomainLockRequest;
use App\Http\Requests\Admin\UpdateDomainContactsRequest;
use App\Http\Requests\Admin\UpdateNameserversRequest;
use App\Models\Contact;
use App\Models\Country;
use App\Models\Domain;
use App\Models\User;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
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
        abort_if(Gate::denies('domain_show'), 403) || $domain->owner_id !== auth()->id() && ! auth()->user()->isAdmin();
        if ($domain->owner_id !== auth()->id() && ! auth()->user()->isAdmin()) {
            return redirect()->to_route('dashboard')->with('error', 'You are not authorized to view this domain.');
        }

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
        } catch (Exception $exception) {
            Log::error('Failed to get domain info: '.$exception->getMessage());

            return view('admin.domains.domainInfo', [
                'domainInfo' => $domain,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    public function refreshInfo(Domain $domain, GetDomainInfoAction $action): RedirectResponse
    {
        $canView = Gate::allows('domain_show') || $domain->owner_id === auth()->id();
        abort_unless($canView, 403);

        $result = $action->handle($domain);

        if ($result['success']) {
            return back()->with('success', $result['message'] ?? 'Domain info refreshed successfully');
        }

        return back()->withErrors(['error' => $result['message'] ?? 'Failed to refresh domain info']);
    }

    public function showTransferForm(Domain $domain): View
    {
        abort_if(Gate::denies('domain_transfer'), 403);

        $contacts = Contact::query()->where('user_id', auth()->id())->get();

        return view('admin.domains.transfer', [
            'domain' => $domain,
            'contacts' => $contacts,
        ]);
    }

    public function transferDomain(Domain $domain, DomainTransferRequest $request, TransferDomainAction $action): RedirectResponse
    {
        $result = $action->handle($domain, $request->validated());

        if ($result['success']) {
            return to_route('admin.domains.index')
                ->with('success', $result['message'] ?? 'Domain transfer initiated successfully');
        }

        return back()
            ->withErrors(['error' => $result['message'] ?? 'Domain transferRegister failed'])
            ->withInput();
    }

    public function ownerShipForm(Domain $domain): View
    {
        abort_if(Gate::denies('domain_renew'), 403) || $domain->owner_id !== auth()->id() && ! auth()->user()->isAdmin();
        if ($domain->owner_id !== auth()->id() && ! auth()->user()->isAdmin()) {
            return redirect()->to_route('dashboard')->with('error', 'You are not authorized to assign owner for this domain.');
        }

        $domain->load(['owner']);

        $users = User::query()->with('roles')->whereHas('roles', function ($query): void {
            $query->where('roles.id', '!=', 1);
        })->where('id', '!=', $domain->owner_id)->get();

        return view('admin.domains.owner', ['domain' => $domain, 'users' => $users]);
    }

    public function assignOwner(Domain $domain, AssignDomainOwnerRequest $request): RedirectResponse
    {
        $domain->update([
            'owner_id' => $request->validated('owner_id'),
        ]);

        return to_route('admin.domains.index')->with('success', 'Owner assigned successfully');
    }

    public function edit(Domain $domain): View|RedirectResponse
    {
        if ($domain->owner_id !== auth()->id() && ! auth()->user()->isAdmin()) {
            return redirect()->to_route('dashboard')->with('error', 'You are not authorized to edit this domain.');
        }

        $countries = Country::query()->select('name', 'iso_code')->get();
        $domain->load(['owner', 'nameservers']);
        $domain->load(['contacts' => function ($query): void {
            $query->withPivot('type', 'user_id')->withoutGlobalScopes();
        }]);
        $user = auth()->user();
        if ($user->isAdmin()) {
            $availableContacts = Contact::query()->withoutGlobalScopes()
                ->orderBy('is_primary', 'desc')
                ->orderBy('first_name')
                ->get();
        } else {
            $availableContacts = Contact::query()->withoutGlobalScopes()
                ->where(function ($query) use ($user, $domain): void {
                    $query->where('user_id', $user->id)
                        ->orWhereHas('domains', function ($q) use ($domain): void {
                            $q->where('domains.id', $domain->id);
                        })
                        ->orWhereNull('user_id');
                })
                ->orderBy('is_primary', 'desc')
                ->orderBy('first_name')
                ->get();
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
        if ($domain->owner_id !== auth()->id() && ! auth()->user()->isAdmin()) {
            return redirect()->to_route('dashboard')->with('error', 'You are not authorized to update nameservers for this domain.');
        }

        $result = $action->handle($domain, $request->validated()['nameservers']);

        if ($result['success']) {
            return to_route('admin.domains.index')
                ->with('success', $result['message'] ?? 'Nameservers updated successfully');
        }

        return back()
            ->withErrors(['error' => $result['message'] ?? 'Nameserver update failed'])
            ->withInput();
    }

    public function toggleLock(Domain $domain, ToggleDomainLockRequest $request, ToggleDomainLockAction $action): RedirectResponse
    {
        if ($domain->owner_id !== auth()->id() && ! auth()->user()->isAdmin()) {
            return redirect()->to_route('dashboard')->with('error', 'You are not authorized to toggle lock for this domain.');
        }

        $desiredLock = $request->has('lock') ? $request->boolean('lock') : null;
        $result = $action->execute($domain, $desiredLock);

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->withErrors(['error' => $result['message'] ?? 'Failed to update domain lock status']);
    }

    public function editContact(Domain $domain, string $type): View|RedirectResponse
    {
        if ($domain->owner_id !== auth()->id() && ! auth()->user()->isAdmin()) {
            return redirect()->to_route('dashboard')->with('error', 'You are not authorized to edit contact for this domain.');
        }

        $validTypes = ['registrant', 'admin', 'technical', 'billing'];
        abort_unless(in_array($type, $validTypes), 404, 'Invalid contact type');

        $domain->load(['contacts' => function ($query): void {
            $query->withPivot('type', 'user_id')->withoutGlobalScopes();
        }]);
        $user = auth()->user();
        if ($user->isAdmin()) {
            $availableContacts = Contact::query()->withoutGlobalScopes()
                ->orderBy('is_primary', 'desc')
                ->orderBy('first_name')
                ->get();
        } else {
            $availableContacts = Contact::query()->withoutGlobalScopes()
                ->where(function ($query) use ($user, $domain): void {
                    $query->where('user_id', $user->id)
                        ->orWhereHas('domains', function ($q) use ($domain): void {
                            $q->where('domains.id', $domain->id);
                        })
                        ->orWhereNull('user_id');
                })
                ->orderBy('is_primary', 'desc')
                ->orderBy('first_name')
                ->get();
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

        $countries = Country::query()->select('name', 'iso_code')->get();

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
        if ($domain->owner_id !== auth()->id() && ! auth()->user()->isAdmin()) {
            return redirect()->to_route('dashboard')->with('error', 'You are not authorized to update contacts for this domain.');
        }

        $result = $action->handle($domain, $request->validated());

        if ($result['success']) {
            return to_route('admin.domains.edit', $domain->uuid)
                ->with('success', 'Domain contacts updated successfully');
        }

        return back()
            ->withErrors(['error' => $result['message'] ?? 'Failed to update domain contacts']);
    }

    public function reactivate(ReactivateDomainRequest $request, ReactivateDomainAction $action): RedirectResponse
    {
        if ($domain->owner_id !== auth()->id() && ! auth()->user()->isAdmin()) {
                return redirect()->to_route('dashboard')->with('error', 'You are not authorized to reactivate this domain.');
        }

        try {
            $domain = Domain::query()->where('name', $request->validated('domain'))->firstOrFail();

            $result = $action->handle($domain);

            if (! $result['success']) {
                return back()->withErrors(['error' => $result['message'] ?? 'Failed to reactivate domain']);
            }

            return back()->with('success', $result['message'] ?? 'Domain reactivated successfully');

        } catch (Exception $exception) {
            Log::error('Domain reactivation controller error', [
                'domain' => $request->validated('domain'),
                'error' => $exception->getMessage(),
            ]);

            return back()->withErrors(['error' => 'Failed to reactivate domain: '.$exception->getMessage()]);
        }
    }
}
