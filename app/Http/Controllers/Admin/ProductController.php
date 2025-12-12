<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\Hosting\BillingCycle;
use App\Helpers\CurrencyHelper;
use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Models\HostingCategory;
use App\Models\HostingPlanPrice;
use App\Models\Subscription;
use Darryldecode\Cart\Exceptions\InvalidItemException;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends Controller
{
    public function domains(): Factory|View
    {
        abort_if(Gate::denies('product_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $domains = Domain::with('owner')->get();

        return view('admin.products.domains', ['domains' => $domains]);
    }

    public function hosting(Request $request): Factory|View
    {
        abort_if(Gate::denies('product_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $categorySlug = $request->string('category_slug')->trim()->toString();

        $subscriptionsQuery = Subscription::with(['user', 'plan.category', 'planPrice'])
            ->where('user_id', auth()->user()->id);

        if ($categorySlug !== '') {
            $subscriptionsQuery->whereHas('plan.category', function ($query) use ($categorySlug): void {
                $query->where('slug', $categorySlug);
            });
        }

        $subscriptions = $subscriptionsQuery->latest()
            ->paginate(25)
            ->withQueryString();

        $categoryIds = Subscription::query()
            ->where('user_id', auth()->user()->id)
            ->join('hosting_plans', 'subscriptions.hosting_plan_id', '=', 'hosting_plans.id')
            ->distinct()
            ->pluck('hosting_plans.category_id');

        $categories = HostingCategory::query()
            ->whereIn('id', $categoryIds)
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return view('admin.products.hosting', [
            'subscriptions' => $subscriptions,
            'categories' => $categories,
            'selectedCategorySlug' => $categorySlug,
        ]);
    }

    public function showSubscription(Request $request, Subscription $subscription): View|Factory
    {
        $user = $request->user();

        // Users can only view their own subscriptions, admins can view all
        abort_if(! $user->isAdmin() && $subscription->user_id !== $user->id, 403, 'Unauthorized');

        $subscription->load(['plan', 'planPrice']);

        return view('admin.products.subscription-detail', [
            'subscription' => $subscription,
        ]);
    }

    /**
     * @throws InvalidItemException
     */
    public function addSubscriptionRenewalToCart(Request $request, Subscription $subscription): RedirectResponse
    {
        $user = $request->user();
        abort_if(! $user->isAdmin() && $subscription->user_id !== $user->id, 403, 'Unauthorized');

        if (! $subscription->canBeRenewed()) {
            return back()->with('error', 'This subscription cannot be renewed at this time.');
        }

        // Get monthly plan price (renewals always use monthly pricing for calculation)
        $monthlyPlanPrice = HostingPlanPrice::query()
            ->where('hosting_plan_id', $subscription->hosting_plan_id)
            ->where('billing_cycle', 'monthly')
            ->where('status', 'active')
            ->first();

        if (! $monthlyPlanPrice) {
            return back()->with('error', 'Unable to find monthly pricing information for this subscription plan.');
        }

        // Get the original billing cycle plan price for display
        $originalBillingCycle = BillingCycle::tryFrom($subscription->billing_cycle) ?? BillingCycle::Monthly;
        $originalPlanPrice = HostingPlanPrice::query()
            ->where('hosting_plan_id', $subscription->hosting_plan_id)
            ->where('billing_cycle', $subscription->billing_cycle)
            ->where('status', 'active')
            ->first();

        // Default quantity to the subscription's original billing cycle in months
        $defaultQuantity = $originalBillingCycle->toMonths();

        // Allow optional quantity parameter from request
        $quantity = max(1, (int) $request->input('quantity', $defaultQuantity));

        $cartId = 'subscription-renewal-'.$subscription->id;

        if (Cart::get($cartId)) {
            return back()->with('info', 'This subscription renewal is already in your cart.');
        }

        $userCurrency = CurrencyHelper::getUserCurrency();
        $monthlyRenewalPrice = $monthlyPlanPrice->getPriceInCurrency('renewal_price', $userCurrency);

        // Calculate unit price for display based on original billing cycle
        $displayUnitPrice = $monthlyRenewalPrice; // Default to monthly
        if ($originalPlanPrice && $originalBillingCycle === BillingCycle::Annually) {
            $displayUnitPrice = $originalPlanPrice->getPriceInCurrency('renewal_price', $userCurrency);
        }

        $totalPrice = $monthlyRenewalPrice * $quantity;

        Cart::add([
            'id' => $cartId,
            'name' => ($subscription->domain ?: 'Hosting').' - '.$subscription->plan->name.' (Renewal)',
            'price' => $monthlyRenewalPrice,
            'quantity' => $quantity,
            'attributes' => [
                'type' => 'subscription_renewal',
                'subscription_id' => $subscription->id,
                'subscription_uuid' => $subscription->uuid,
                'domain' => $subscription->domain,
                'domain_name' => $subscription->domain ?? 'N/A',
                'billing_cycle' => $subscription->billing_cycle, // Store original billing cycle for reference
                'hosting_plan_id' => $subscription->hosting_plan_id,
                'hosting_plan_price_id' => $monthlyPlanPrice->id,
                'current_expiry' => $subscription->expires_at->format('Y-m-d'),
                'currency' => $userCurrency,
                'unit_price' => $monthlyRenewalPrice, // Monthly price for calculations
                'display_unit_price' => $displayUnitPrice, // Display price based on original billing cycle
                'total_price' => $totalPrice,
                'duration_months' => $quantity,
                'added_at' => now()->timestamp,
                'metadata' => [
                    'hosting_plan_id' => $subscription->hosting_plan_id,
                    'hosting_plan_price_id' => $monthlyPlanPrice->id,
                    'billing_cycle' => $subscription->billing_cycle,
                    'subscription_id' => $subscription->id,
                ],
            ],
        ]);

        $periodLabel = $quantity === 1 ? '1 month' : sprintf('%d months', $quantity);

        return to_route('cart.index')->with('success', sprintf('Subscription renewal added to cart for %s.', $periodLabel));
    }
}
