<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Domains\GetDomainInfoAction;
use App\Actions\Domains\ListDomainAction;
use App\Actions\Domains\RenewDomainAction;
use App\Actions\Domains\ToggleDomainLockAction;
use App\Actions\Domains\TransferDomainAction;
use App\Actions\Domains\UpdateDomainContactsAction;
use App\Actions\Domains\UpdateNameserversAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DomainRenewalRequest;
use App\Http\Requests\Admin\DomainTransferRequest;
use App\Http\Requests\Admin\UpdateNameserversRequest;
use App\Http\Requests\ToggleDomainLockRequest;
use App\Models\Contact;
use App\Models\Domain;
use App\Models\DomainPrice;
use Exception;
use Gate;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

final class DomainController extends Controller
{
    public function index(ListDomainAction $action)
    {
        abort_if(Gate::denies('domain_access'), 403);
        $domains = $action->handle();

        return view('admin.domains.index', ['domains' => $domains]);
    }

    public function domainInfo(Domain $domain, GetDomainInfoAction $action): View
    {
        abort_if(Gate::denies('domain_show'), 403);

        try {
            // Get latest info from registrar
            $registrarInfo = $action->handle($domain);

            // Load relationships
            $domain->load(['nameservers']);
            $domain->load(['contacts' => function ($query) {
                $query->where('contacts.user_id', auth()->id());
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

    public function showNameserversForm(Domain $domain): View
    {
        abort_if(Gate::denies('domain_edit'), 403);

        $domain->load('nameservers');

        return view('admin.domains.nameservers', [
            'domain' => $domain,
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

    public function toggleLock(ToggleDomainLockRequest $request, ToggleDomainLockAction $action): RedirectResponse {
        abort_if(Gate::denies('domain_edit'), 403);

        $domain = Domain::findOrFail($request->validated()['domain_id']);
        $lock = (bool) $request->validated()['lock'];
        $result = $action->execute($domain, $lock);

        if ($result['success']) {
            return redirect()->back()->with('success', $result['message']);
        }

        return redirect()->back()->withErrors(['error' => $result['message'] ?? 'Failed to update domain lock status']);
    }

    public function refreshDomainInfo(Domain $domain, GetDomainInfoAction $action): RedirectResponse
    {
        abort_if(Gate::denies('domain_show') || $domain->owner_id !== auth()->id(), 403);

        $result = $action->handle($domain);

        if ($result['success']) {
            return redirect()->back()
                ->with('success', 'Domain information updated successfully');
        }

        return redirect()->back()
            ->withErrors(['error' => $result['message'] ?? 'Failed to update domain information']);
    }

    public function updateContacts(Domain $domain, Request $request, UpdateDomainContactsAction $action): RedirectResponse
    {
        abort_if(Gate::denies('domain_edit') || $domain->owner_id !== auth()->id(), 403);

        $validated = $request->validate([
            'registrant.contact_id' => 'required|exists:contacts,id',
            'admin.contact_id' => 'required|exists:contacts,id',
            'technical.contact_id' => 'required|exists:contacts,id',
            'billing.contact_id' => 'required|exists:contacts,id',
        ]);

        $result = $action->handle($domain, $validated);

        if ($result['success']) {
            return redirect()->back()
                ->with('success', 'Domain contacts updated successfully');
        }

        return redirect()->back()
            ->withErrors(['error' => $result['message'] ?? 'Failed to update domain contacts']);
    }



    private function extractTld(string $domain): string
    {
        $parts = explode('.', $domain);

        return '.'.end($parts);
    }
}
