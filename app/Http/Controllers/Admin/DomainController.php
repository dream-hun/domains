<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Domains\ListDomainAction;
use App\Actions\Domains\RenewDomainAction;
use App\Actions\Domains\TransferDomainAction;
use App\Actions\Domains\UpdateNameserversAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DomainRenewalRequest;
use App\Http\Requests\Admin\DomainTransferRequest;
use App\Http\Requests\Admin\UpdateNameserversRequest;
use App\Models\Contact;
use App\Models\Domain;
use App\Models\DomainPrice;
use Exception;
use Gate;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

final class DomainController extends Controller
{
    public function index(ListDomainAction $action)
    {
        abort_if(Gate::denies('domain_access'), 403);
        $domains = $action->handle();

        return view('admin.domains.index', ['domains' => $domains]);
    }

    public function domainInfo(Domain $domain): View
    {
        abort_if(Gate::denies('domain_show'), 403);

        try {
            // Always load relationships for the domain
            $domain->load(['nameservers', 'contacts']);

            return view('admin.domains.domainInfo', [
                'domainInfo' => $domain,
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
            ->withErrors(['error' => $result['message'] ?? 'Domain transfer failed'])
            ->withInput();
    }

    public function showRenewForm(Domain $domain): View
    {
        abort_if(Gate::denies('domain_renew'), 403);

        // Get pricing information for the domain TLD
        $tld = $this->extractTld($domain->name);
        $domainPrice = DomainPrice::where('tld', $tld)->first();

        $pricing = [];
        if ($domainPrice) {
            // Generate pricing for 1-10 years
            for ($i = 1; $i <= 10; $i++) {
                $pricing[$i] = ($domainPrice->renew_price ?? $domainPrice->register_price) * $i / 100;
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
        abort_if(Gate::denies('domain_update'), 403);

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

    private function extractTld(string $domain): string
    {
        $parts = explode('.', $domain);

        return '.'.end($parts);
    }
}
