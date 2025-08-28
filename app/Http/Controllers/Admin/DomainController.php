<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Domains\ListDomainAction;
use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Services\Domain\NamecheapDomainService;
use Exception;
use Gate;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;

final class DomainController extends Controller
{
    public function index(ListDomainAction $action)
    {
        abort_if(Gate::denies('domain_access'), 403);
        $domains = $action->handle();

        return view('admin.domains.index', ['domains' => $domains]);
    }

    public function domainInfo(Domain $domain, NamecheapDomainService $namecheapDomainService): View
    {
        abort_if(Gate::denies('domain_show'), 403);

        try {
            // Always load relationships for the domain
            $domain->load(['nameservers', 'contacts']);

            // Optionally, fetch provider info (but do not overwrite the model)
            $providerInfo = null;
            if ($domain->provider === 'Namecheap') {
                $providerInfo = $namecheapDomainService->getDomainInfo($domain->name);
                // Optionally, you can update the model with provider info here if needed
            }

            return view('admin.domains.domainInfo', [
                'domainInfo' => $domain,
                'providerInfo' => $providerInfo,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to get domain info: '.$e->getMessage());

            return view('admin.domains.domainInfo', [
                'domainInfo' => $domain,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
