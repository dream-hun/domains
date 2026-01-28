<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Subscription\CreateCustomSubscriptionAction;
use App\Enums\Hosting\BillingCycle;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateCustomSubscriptionRequest;
use App\Http\Requests\Admin\UpdateSubscriptionRequest;
use App\Models\HostingPlan;
use App\Models\HostingPlanPrice;
use App\Models\Subscription;
use App\Models\User;
use App\Services\CurrencyService;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Throwable;

final class SubscriptionController extends Controller
{
    public function __construct(
        private readonly CurrencyService $currencyService
    ) {}

    public function create(): View|Factory
    {
        abort_if(Gate::denies('subscription_create'), 403);

        $users = User::query()
            ->select('id', 'first_name', 'last_name', 'email')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $hostingPlans = HostingPlan::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $currencies = $this->currencyService->getActiveCurrencies();

        return view('admin.subscriptions.create', [
            'users' => $users,
            'hostingPlans' => $hostingPlans,
            'currencies' => $currencies,
        ]);
    }

    public function store(CreateCustomSubscriptionRequest $request, CreateCustomSubscriptionAction $action): RedirectResponse
    {
        try {
            $result = $action->handle($request->validated(), auth()->id());

            return to_route('admin.subscriptions.show', $result['subscription'])
                ->with('success', 'Custom subscription created successfully.');
        } catch (Exception $exception) {
            Log::error('Failed to create custom subscription', [
                'admin_user_id' => auth()->id(),
                'error' => $exception->getMessage(),
                'data' => $request->validated(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Failed to create custom subscription: '.$exception->getMessage());
        }
    }

    public function index(Request $request): View|Factory
    {
        abort_if(Gate::denies('subscription_access'), 403);

        $filters = [
            'status' => $request->string('status')->trim()->toString(),
            'billing_cycle' => $request->string('billing_cycle')->trim()->toString(),
            'search' => $request->string('search')->trim()->toString(),
            'starts_from' => $request->string('starts_from')->trim()->toString(),
            'starts_to' => $request->string('starts_to')->trim()->toString(),
        ];

        $perPage = $request->integer('per_page', 25);
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        $subscriptionsQuery = Subscription::query()
            ->with([
                'user:id,first_name,last_name,email',
                'plan:id,name',
                'planPrice:id,hosting_plan_id,billing_cycle,regular_price,renewal_price',
                'createdByAdmin:id,first_name,last_name',
            ]);

        if ($filters['status'] !== '') {
            $subscriptionsQuery->where('status', $filters['status']);
        }

        if ($filters['billing_cycle'] !== '') {
            $subscriptionsQuery->where('billing_cycle', $filters['billing_cycle']);
        }

        if ($filters['search'] !== '') {
            $searchTerm = '%'.$filters['search'].'%';

            $subscriptionsQuery->where(function ($query) use ($searchTerm): void {
                $query->where('domain', 'like', $searchTerm)
                    ->orWhere('provider_resource_id', 'like', $searchTerm)
                    ->orWhereHas('user', function ($userQuery) use ($searchTerm): void {
                        $userQuery->where('first_name', 'like', $searchTerm)
                            ->orWhere('last_name', 'like', $searchTerm)
                            ->orWhere('email', 'like', $searchTerm);
                    });
            });
        }

        if ($filters['starts_from'] !== '') {
            $this->applyDateFilter($subscriptionsQuery, 'starts_at', '>=', $filters['starts_from']);
        }

        if ($filters['starts_to'] !== '') {
            $this->applyDateFilter($subscriptionsQuery, 'starts_at', '<=', $filters['starts_to']);
        }

        $subscriptions = $subscriptionsQuery
            ->latest('starts_at')
            ->paginate($perPage)
            ->withQueryString();

        $statsQuery = Subscription::query();
        $now = Date::now();

        $stats = [
            'total' => (clone $statsQuery)->count(),
            'active' => (clone $statsQuery)->where('status', 'active')->count(),
            'expiring_soon' => (clone $statsQuery)
                ->where('status', 'active')
                ->whereBetween('expires_at', [$now, $now->copy()->addDays(30)])
                ->count(),
            'cancelled' => (clone $statsQuery)->whereNotNull('cancelled_at')->count(),
        ];

        $statusOptions = Subscription::query()
            ->select('status')
            ->distinct()
            ->whereNotNull('status')
            ->orderBy('status')
            ->pluck('status')
            ->map(static fn ($value): string => (string) $value)
            ->values();

        $billingCycleOptions = Subscription::query()
            ->select('billing_cycle')
            ->distinct()
            ->whereNotNull('billing_cycle')
            ->orderBy('billing_cycle')
            ->pluck('billing_cycle')
            ->map(static fn ($value): string => (string) $value)
            ->values();

        return view('admin.subscriptions.index', [
            'subscriptions' => $subscriptions,
            'filters' => array_merge($filters, ['per_page' => $perPage]),
            'stats' => $stats,
            'statusOptions' => $statusOptions,
            'billingCycleOptions' => $billingCycleOptions,
        ]);
    }

    public function show(Subscription $subscription): View|Factory
    {
        abort_if(Gate::denies('subscription_show'), 403);

        $subscription->load(['user', 'plan', 'planPrice', 'createdByAdmin']);

        return view('admin.subscriptions.show', [
            'subscription' => $subscription,
        ]);
    }

    public function edit(Subscription $subscription): View|Factory
    {
        abort_if(Gate::denies('subscription_edit'), 403);

        $subscription->load(['user', 'plan', 'planPrice']);

        $statusOptions = ['active', 'expired', 'cancelled', 'suspended'];

        return view('admin.subscriptions.edit', [
            'subscription' => $subscription,
            'statusOptions' => $statusOptions,
        ]);
    }

    public function update(UpdateSubscriptionRequest $request, Subscription $subscription): RedirectResponse
    {
        $subscription->update([
            'status' => $request->input('status'),
            'starts_at' => $request->input('starts_at'),
            'expires_at' => $request->input('expires_at'),
            'next_renewal_at' => $request->input('next_renewal_at'),
            'domain' => $request->input('domain'),
            'auto_renew' => $request->boolean('auto_renew'),
        ]);

        Log::info('Subscription updated by admin', [
            'subscription_id' => $subscription->id,
            'admin_user_id' => auth()->id(),
            'changes' => $request->validated(),
        ]);

        return to_route('admin.subscriptions.show', $subscription)
            ->with('success', 'Subscription updated successfully.');
    }

    public function renewNow(Request $request, Subscription $subscription): RedirectResponse
    {
        abort_if(Gate::denies('subscription_edit'), 403);

        if (! $subscription->canBeRenewed()) {
            return back()->with('error', 'This subscription cannot be renewed at this time.');
        }

        $isComp = $request->boolean('is_comp', false);
        $compReason = $request->string('comp_reason')->trim()->toString();

        if (! $isComp) {
            return back()->with('error', 'Admin renewals require explicit "comp" flag. Please use the comp renewal option or create a payment order.');
        }

        if (empty($compReason)) {
            return back()->with('error', 'Comp renewal reason is required for audit purposes.');
        }

        try {
            $billingCycleValue = $request->string('billing_cycle')->trim()->toString() ?: $subscription->billing_cycle;
            $billingCycle = $this->resolveBillingCycle($billingCycleValue);

            $planPrice = HostingPlanPrice::query()
                ->where('hosting_plan_id', $subscription->hosting_plan_id)
                ->where('billing_cycle', $billingCycle->value)
                ->where('status', 'active')
                ->first();

            if (! $planPrice) {
                throw new Exception('No active pricing found for billing cycle '.$billingCycle->value);
            }

            $renewalSnapshot = [
                'id' => $planPrice->id,
                'regular_price' => $planPrice->regular_price,
                'renewal_price' => $planPrice->renewal_price,
                'billing_cycle' => $planPrice->billing_cycle,
                'comp_reason' => $compReason,
                'comp_admin_id' => auth()->id(),
            ];

            $subscription->extendSubscription(
                $billingCycle,
                paidAmount: null,
                validatePayment: false,
                isComp: true,
                renewalSnapshot: $renewalSnapshot
            );

            Log::info('Subscription renewed manually by admin (comp)', [
                'subscription_id' => $subscription->id,
                'admin_user_id' => auth()->id(),
                'billing_cycle' => $billingCycle->value,
                'comp_reason' => $compReason,
                'new_expiry' => $subscription->expires_at->toDateString(),
            ]);

            return back()->with('success', 'Subscription renewed successfully (comp). New expiry date: '.$subscription->expires_at->format('F d, Y'));
        } catch (Throwable $throwable) {
            Log::error('Failed to renew subscription manually', [
                'subscription_id' => $subscription->id,
                'admin_user_id' => auth()->id(),
                'error' => $throwable->getMessage(),
            ]);

            return back()->with('error', 'Failed to renew subscription: '.$throwable->getMessage());
        }
    }

    private function resolveBillingCycle(string $cycle): BillingCycle
    {
        foreach (BillingCycle::cases() as $case) {
            if ($case->value === $cycle) {
                return $case;
            }
        }

        return BillingCycle::Monthly;
    }

    private function applyDateFilter(Builder $query, string $column, string $operator, string $value): void
    {
        try {
            $date = Date::parse($value)->startOfDay();

            $query->whereDate($column, $operator, $date);
        } catch (Throwable) {
            // Ignore invalid date input
        }
    }
}
