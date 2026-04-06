<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Domains\GetDomainInfoAction;
use App\Actions\Domains\ListDomainAction;
use App\Actions\Domains\ReactivateDomainAction;
use App\Actions\Domains\RegisterCustomDomainAction;
use App\Actions\Domains\ToggleDomainLockAction;
use App\Actions\Domains\TransferDomainAction;
use App\Actions\Domains\UpdateDomainContactsAction;
use App\Actions\Domains\UpdateNameserversAction;
use App\Actions\Subscription\CreateCustomSubscriptionAction;
use App\Enums\DomainStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignDomainOwnerRequest;
use App\Http\Requests\Admin\CreateCustomDomainRegistrationRequest;
use App\Http\Requests\Admin\DomainTransferRequest;
use App\Http\Requests\Admin\ReactivateDomainRequest;
use App\Http\Requests\Admin\ToggleDomainLockRequest;
use App\Http\Requests\Admin\UpdateCustomDomainRegistrationRequest;
use App\Http\Requests\Admin\UpdateDomainContactsRequest;
use App\Http\Requests\Admin\UpdateNameserversRequest;
use App\Models\Contact;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Domain;
use App\Models\HostingPlan;
use App\Models\Subscription;
use App\Models\User;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
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

    public function createCustom(): View|Factory
    {
        abort_if(Gate::denies('domain_create'), 403);

        $users = User::query()
            ->select('id', 'first_name', 'last_name', 'email')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $contacts = Contact::query()
            ->withoutGlobalScopes()
            ->orderBy('first_name')
            ->get();

        $hostingPlans = HostingPlan::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $subscriptions = Subscription::query()
            ->with(['user:id,first_name,last_name,email', 'plan:id,name'])
            ->where('status', 'active')->latest()
            ->get();

        $currencies = Currency::getActiveCurrencies();

        return view('admin.domains.create-custom', [
            'users' => $users,
            'contacts' => $contacts,
            'hostingPlans' => $hostingPlans,
            'subscriptions' => $subscriptions,
            'currencies' => $currencies,
        ]);
    }

    public function storeCustom(
        CreateCustomDomainRegistrationRequest $request,
        RegisterCustomDomainAction $action
    ): RedirectResponse {
        $result = $action->handle($request->validated(), auth()->id());

        if ($result['success']) {
            return to_route('admin.domains.index')
                ->with('success', $result['message']);
        }

        return back()
            ->withInput()
            ->with('error', $result['message']);
    }

    public function editCustom(Domain $domain): View|Factory
    {
        abort_if(Gate::denies('domain_edit'), 403);

        $domain->load(['owner', 'subscription.plan']);

        $users = User::query()
            ->select('id', 'first_name', 'last_name', 'email')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $hostingPlans = HostingPlan::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $subscriptions = Subscription::query()
            ->with(['user:id,first_name,last_name,email', 'plan:id,name'])
            ->where('status', 'active')
            ->latest()
            ->get();

        $currencies = Currency::getActiveCurrencies();

        return view('admin.domains.edit-custom', [
            'domain' => $domain,
            'users' => $users,
            'hostingPlans' => $hostingPlans,
            'subscriptions' => $subscriptions,
            'currencies' => $currencies,
            'domainStatuses' => DomainStatus::cases(),
        ]);
    }

    public function updateCustom(
        UpdateCustomDomainRegistrationRequest $request,
        Domain $domain,
        CreateCustomSubscriptionAction $createCustomSubscriptionAction
    ): RedirectResponse {
        $customPrice = $request->input('custom_price');

        $domain->update([
            'owner_id' => $request->input('owner_id'),
            'years' => $request->integer('years'),
            'status' => $request->input('status'),
            'auto_renew' => $request->boolean('auto_renew'),
            'registered_at' => $request->input('registered_at'),
            'expires_at' => $request->input('expires_at'),
            'custom_price' => $customPrice,
            'custom_price_currency' => $request->input('custom_price_currency'),
            'is_custom_price' => $customPrice !== null && $customPrice !== '',
            'custom_price_notes' => $request->input('custom_price_notes'),
        ]);

        $this->handleSubscriptionOption($domain, $request, $createCustomSubscriptionAction);

        Log::info('Domain registration updated by admin', [
            'domain_id' => $domain->id,
            'domain_name' => $domain->name,
            'admin_user_id' => auth()->id(),
            'changes' => $request->validated(),
        ]);

        return to_route('admin.domains.info', $domain)
            ->with('success', 'Domain registration updated successfully.');
    }

    public function domainInfo(Domain $domain, GetDomainInfoAction $action): Factory|View|\Illuminate\View\View
    {
        abort_if(Gate::denies('domain_show'), 403);

        if ($domain->owner_id !== auth()->id() && ! auth()->user()->isAdmin()) {
            return to_route('dashboard')->with('error', 'You are not authorized to view this domain.');
        }

        try {

            $registrarInfo = $action->handle($domain);
            $domain->load(['nameservers']);
            $domain->load(['contacts' => function (mixed $query): void {
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
            ->withErrors(['error' => $result['message'] ?? 'Domain transfer failed'])
            ->withInput();
    }

    public function ownerShipForm(Domain $domain): View|RedirectResponse
    {
        abort_if(Gate::denies('domain_renew'), 403);
        if ($domain->owner_id !== auth()->id() && ! auth()->user()->isAdmin()) {
            return to_route('dashboard')->with('error', 'You are not authorized to assign owner for this domain.');
        }

        $domain->load(['owner']);

        $users = User::query()->with('roles')->whereHas('roles', function (Builder $query): void {
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
            return to_route('dashboard')->with('error', 'You are not authorized to edit this domain.');
        }

        $countries = Country::query()->select('name', 'iso_code')->get();
        $domain->load(['owner', 'nameservers']);
        $domain->load(['contacts' => function (mixed $query): void {
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
                ->where(function (Builder $query) use ($user, $domain): void {
                    $query->where('user_id', $user->id)
                        ->orWhereHas('domains', function (Builder $q) use ($domain): void {
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
            return to_route('dashboard')->with('error', 'You are not authorized to update nameservers for this domain.');
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
            return to_route('dashboard')->with('error', 'You are not authorized to toggle lock for this domain.');
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
            return to_route('dashboard')->with('error', 'You are not authorized to edit contact for this domain.');
        }

        $validTypes = ['registrant', 'admin', 'technical', 'billing'];
        abort_unless(in_array($type, $validTypes), 404, 'Invalid contact type');

        $domain->load(['contacts' => function (mixed $query): void {
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
                ->where(function (Builder $query) use ($user, $domain): void {
                    $query->where('user_id', $user->id)
                        ->orWhereHas('domains', function (Builder $q) use ($domain): void {
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

        $countries = Country::query()->select('name', 'iso_code', 'iso_alpha2')->get();

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
            return to_route('dashboard')->with('error', 'You are not authorized to update contacts for this domain.');
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
        try {
            $domain = Domain::query()->where('name', $request->validated('domain'))->firstOrFail();

            if ($domain->owner_id !== auth()->id() && ! auth()->user()->isAdmin()) {
                return to_route('dashboard')->with('error', 'You are not authorized to reactivate this domain.');
            }

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

    private function handleSubscriptionOption(
        Domain $domain,
        UpdateCustomDomainRegistrationRequest $request,
        CreateCustomSubscriptionAction $createCustomSubscriptionAction
    ): void {
        $option = $request->input('subscription_option', 'keep_current');

        match ($option) {
            'none' => $domain->update(['subscription_id' => null]),
            'create_new' => $this->createAndLinkSubscription($domain, $request, $createCustomSubscriptionAction),
            'link_existing' => $this->linkExistingSubscriptionToDomain($domain, $request),
            default => null, // keep_current — no change
        };
    }

    private function createAndLinkSubscription(
        Domain $domain,
        UpdateCustomDomainRegistrationRequest $request,
        CreateCustomSubscriptionAction $createCustomSubscriptionAction
    ): void {
        $subscriptionData = [
            'user_id' => $domain->owner_id,
            'hosting_plan_id' => $request->input('hosting_plan_id'),
            'billing_cycle' => $request->input('billing_cycle'),
            'domain' => $domain->name,
            'starts_at' => $request->input('hosting_starts_at'),
            'expires_at' => $request->input('hosting_expires_at'),
            'auto_renew' => $request->boolean('hosting_auto_renew'),
        ];

        if ($request->filled('hosting_custom_price') && (float) $request->input('hosting_custom_price') > 0) {
            $subscriptionData['custom_price'] = $request->input('hosting_custom_price');
            $subscriptionData['custom_price_currency'] = $request->input('hosting_custom_price_currency', 'USD');
            $subscriptionData['custom_price_notes'] = $request->input('hosting_custom_price_notes');
        }

        $subscription = $createCustomSubscriptionAction->handle($subscriptionData, (int) auth()->id())['subscription'];
        $domain->update(['subscription_id' => $subscription->id]);

        Log::info('New subscription created and linked to domain during edit', [
            'domain_id' => $domain->id,
            'subscription_id' => $subscription->id,
        ]);
    }

    private function linkExistingSubscriptionToDomain(Domain $domain, UpdateCustomDomainRegistrationRequest $request): void
    {
        $subscription = Subscription::query()->findOrFail(
            $request->integer('existing_subscription_id')
        );

        $domain->update(['subscription_id' => $subscription->id]);

        if (empty($subscription->domain)) {
            $subscription->update(['domain' => $domain->name]);
        }

        Log::info('Domain linked to existing subscription during edit', [
            'domain_id' => $domain->id,
            'subscription_id' => $subscription->id,
        ]);
    }
}
