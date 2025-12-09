<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Hosting\BillingCycle;
use App\Helpers\CurrencyHelper;
use App\Http\Requests\AddSubscriptionRenewalToCartRequest;
use App\Models\HostingPlanPrice;
use App\Models\Subscription;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class SubscriptionRenewalController extends Controller
{
    /**
     * Show the renewal page for a subscription
     */
    public function show(Subscription $subscription): View
    {
        abort_if($subscription->user_id !== auth()->id() && ! auth()->user()->isAdmin(), 403, 'You do not have permission to renew this subscription.');

        abort_unless($subscription->canBeRenewed(), 400, 'This subscription cannot be renewed at this time.');

        $subscription->load(['plan', 'planPrice']);

        // Get user's preferred currency
        $userCurrency = CurrencyHelper::getUserCurrency();

        // Get available billing cycles for this plan
        $availableBillingCycles = HostingPlanPrice::query()
            ->where('hosting_plan_id', $subscription->hosting_plan_id)
            ->where('status', 'active')
            ->get()
            ->mapWithKeys(function (HostingPlanPrice $price) use ($userCurrency) {
                $cycle = BillingCycle::tryFrom($price->billing_cycle);
                if ($cycle) {
                    return [$price->billing_cycle => [
                        'cycle' => $cycle,
                        'renewal_price' => $price->getPriceInCurrency('renewal_price', $userCurrency),
                        'regular_price' => $price->getPriceInCurrency('regular_price', $userCurrency),
                    ]];
                }

                return [];
            });

        // Get current renewal price for the subscription's current billing cycle
        $currentBillingCycle = BillingCycle::tryFrom($subscription->billing_cycle) ?? BillingCycle::Monthly;
        $currentPrice = $availableBillingCycles[$subscription->billing_cycle] ?? null;

        return view('subscriptions.renew', [
            'subscription' => $subscription,
            'availableBillingCycles' => $availableBillingCycles,
            'currentBillingCycle' => $currentBillingCycle,
            'currentPrice' => $currentPrice,
        ]);
    }

    /**
     * Add a subscription renewal to the cart
     */
    public function addToCart(AddSubscriptionRenewalToCartRequest $request, Subscription $subscription): RedirectResponse|JsonResponse
    {
        abort_if($subscription->user_id !== auth()->id() && ! auth()->user()->isAdmin(), 403, 'You do not have permission to renew this subscription.');

        abort_unless($subscription->canBeRenewed(), 400, 'This subscription cannot be renewed at this time.');

        $billingCycleValue = $request->validated()['billing_cycle'];
        $billingCycle = BillingCycle::tryFrom($billingCycleValue);

        if (! $billingCycle) {
            return $this->respondRenewalError($request, 'Invalid billing cycle selected.', 422);
        }

        // Get the plan price for the selected billing cycle
        $planPrice = HostingPlanPrice::query()
            ->where('hosting_plan_id', $subscription->hosting_plan_id)
            ->where('billing_cycle', $billingCycleValue)
            ->where('status', 'active')
            ->first();

        if (! $planPrice) {
            return $this->respondRenewalError($request, 'Pricing information not available for the selected billing cycle.', 404);
        }

        $userCurrency = CurrencyHelper::getUserCurrency();
        $renewalPrice = $planPrice->getPriceInCurrency('renewal_price', $userCurrency);
        $cartId = 'subscription-renewal-'.$subscription->id;

        if (Cart::get($cartId)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This subscription renewal is already in your cart',
                ], 400);
            }

            return back()->with('warning', 'This subscription renewal is already in your cart');
        }

        Cart::add([
            'id' => $cartId,
            'name' => ($subscription->domain ?: 'Hosting').' - '.$subscription->plan->name.' (Renewal)',
            'price' => $renewalPrice,
            'quantity' => 1,
            'attributes' => [
                'type' => 'subscription_renewal',
                'subscription_id' => $subscription->id,
                'subscription_uuid' => $subscription->uuid,
                'billing_cycle' => $billingCycleValue,
                'hosting_plan_id' => $subscription->hosting_plan_id,
                'hosting_plan_price_id' => $planPrice->id,
                'domain' => $subscription->domain,
                'current_expiry' => $subscription->expires_at?->format('Y-m-d'),
                'currency' => $userCurrency,
                'unit_price' => $renewalPrice,
                'total_price' => $renewalPrice,
                'added_at' => now()->timestamp,
            ],
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => sprintf('Subscription renewal added to cart for %s billing cycle.', $billingCycle->label()),
            ]);
        }

        return to_route('checkout.index')
            ->with('success', sprintf('Subscription renewal added to cart for %s billing cycle.', $billingCycle->label()));
    }

    private function respondRenewalError(AddSubscriptionRenewalToCartRequest $request, string $message, int $status): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], $status);
        }

        return back()
            ->withInput()
            ->with('error', $message);
    }
}
