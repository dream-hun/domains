<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Helpers\CurrencyHelper;
use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Models\Subscription;
use App\Services\CurrencyService;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends Controller
{
    public function __construct(
        private readonly CurrencyService $currencyService
    ) {}

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

        $planPrice = $subscription->planPrice;

        if (! $planPrice) {
            return back()->with('error', 'Unable to find pricing information for this subscription.');
        }

        // Create unique cart ID for subscription renewal
        $cartId = 'subscription-renewal-'.$subscription->id;

        // Check if already in cart
        if (Cart::get($cartId)) {
            return back()->with('info', 'This subscription renewal is already in your cart.');
        }

        // Get user's selected currency
        $currency = CurrencyHelper::getUserCurrency();

        // Convert price to selected currency
        $renewalPrice = $this->currencyService->convert(
            $planPrice->renewal_price,
            'USD', // Assuming prices are stored in USD
            $currency
        );

        // Add to cart
        Cart::add([
            'id' => $cartId,
            'name' => ($subscription->plan?->name ?? 'Hosting Plan').' Renewal',
            'price' => $renewalPrice,
            'quantity' => 1,
            'attributes' => [
                'type' => 'subscription_renewal',
                'subscription_id' => $subscription->id,
                'subscription_uuid' => $subscription->uuid,
                'domain_name' => $subscription->domain ?? 'N/A',
                'billing_cycle' => $subscription->billing_cycle,
                'currency' => $currency,
                'unit_price' => $renewalPrice,
                'total_price' => $renewalPrice,
                'added_at' => now()->timestamp,
                'metadata' => [
                    'hosting_plan_id' => $subscription->hosting_plan_id,
                    'hosting_plan_price_id' => $subscription->hosting_plan_price_id,
                    'billing_cycle' => $subscription->billing_cycle,
                    'subscription_id' => $subscription->id,
                ],
            ],
        ]);

        return redirect()->route('cart.index')->with('success', 'Subscription renewal added to cart.');
    }
}
