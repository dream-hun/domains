<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\Hosting\BillingCycle;
use App\Helpers\CurrencyHelper;
use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Models\HostingPlanPrice;
use App\Models\Subscription;
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

    public function hosting(): Factory|View
    {
        abort_if(Gate::denies('product_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $subscriptions = Subscription::with(['user', 'plan', 'planPrice'])
            ->where('user_id', auth()->user()->id)->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.products.hosting', ['subscriptions' => $subscriptions]);
    }

    public function showSubscription(Request $request, Subscription $subscription): View|Factory
    {
        $user = $request->user();

        // Users can only view their own subscriptions, admins can view all
        if (! $user->isAdmin() && $subscription->user_id !== $user->id) {
            abort(403, 'Unauthorized');
        }

        $subscription->load(['plan', 'planPrice']);

        return view('admin.products.subscription-detail', [
            'subscription' => $subscription,
        ]);
    }

    public function addSubscriptionRenewalToCart(Request $request, Subscription $subscription): RedirectResponse
    {
        $user = $request->user();

        // Users can only renew their own subscriptions
        if (! $user->isAdmin() && $subscription->user_id !== $user->id) {
            abort(403, 'Unauthorized');
        }

        if (! $subscription->canBeRenewed()) {
            return back()->with('error', 'This subscription cannot be renewed at this time.');
        }

        $planPrice = HostingPlanPrice::query()
            ->where('hosting_plan_id', $subscription->hosting_plan_id)
            ->where('billing_cycle', $subscription->billing_cycle)
            ->where('status', 'active')
            ->first();

        if (! $planPrice) {
            return back()->with('error', 'Unable to find pricing information for this subscription\'s billing cycle.');
        }

        $cartId = 'subscription-renewal-'.$subscription->id;

        if (Cart::get($cartId)) {
            return back()->with('info', 'This subscription renewal is already in your cart.');
        }

        $userCurrency = CurrencyHelper::getUserCurrency();
        $renewalPrice = $planPrice->getPriceInCurrency('renewal_price', $userCurrency);
        $billingCycle = BillingCycle::tryFrom($subscription->billing_cycle);

        Cart::add([
            'id' => $cartId,
            'name' => ($subscription->domain ?: 'Hosting').' - '.($subscription->plan?->name ?? 'Hosting Plan').' (Renewal)',
            'price' => $renewalPrice,
            'quantity' => 1,
            'attributes' => [
                'type' => 'subscription_renewal',
                'subscription_id' => $subscription->id,
                'subscription_uuid' => $subscription->uuid,
                'domain' => $subscription->domain,
                'domain_name' => $subscription->domain ?? 'N/A',
                'billing_cycle' => $subscription->billing_cycle,
                'hosting_plan_id' => $subscription->hosting_plan_id,
                'hosting_plan_price_id' => $planPrice->id,
                'current_expiry' => $subscription->expires_at?->format('Y-m-d'),
                'currency' => $userCurrency,
                'unit_price' => $renewalPrice,
                'total_price' => $renewalPrice,
                'added_at' => now()->timestamp,
                'metadata' => [
                    'hosting_plan_id' => $subscription->hosting_plan_id,
                    'hosting_plan_price_id' => $planPrice->id,
                    'billing_cycle' => $subscription->billing_cycle,
                    'subscription_id' => $subscription->id,
                ],
            ],
        ]);

        return redirect()->route('cart.index')->with('success', sprintf('Subscription renewal added to cart for %s billing cycle.', $billingCycle?->label() ?? $subscription->billing_cycle));
    }
}
