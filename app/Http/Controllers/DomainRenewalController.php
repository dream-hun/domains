<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\AddDomainRenewalToCartRequest;
use App\Models\Domain;
use App\Services\RenewalService;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

final class DomainRenewalController extends Controller
{
    /**
     * Show the renewal page for a domain
     */
    public function show(Domain $domain): View
    {
        $renewalService = app(RenewalService::class);

        abort_if($domain->owner_id !== auth()->id() && ! auth()->user()->isAdmin(), 403, 'You do not have permission to renew this domain.');

        $domainPrice = $renewalService->resolveDomainPrice($domain);

        abort_unless($domainPrice !== null, 404, 'Pricing information not available for this domain.');

        $domain->setRelation('domainPrice', $domainPrice);

        $priceData = $renewalService->getRenewalPrice($domain, 1);

        return view('domains.renew', [
            'domain' => $domain,
            'domainPrice' => $domainPrice,
            'renewalPrice' => $priceData['unit_price'],
            'currency' => $priceData['currency'],
        ]);
    }

    /**
     * Add a domain renewal to the cart
     */
    public function addToCart(AddDomainRenewalToCartRequest $request, Domain $domain): RedirectResponse|JsonResponse
    {
        $renewalService = app(RenewalService::class);

        abort_if($domain->owner_id !== auth()->id() && ! auth()->user()->isAdmin(), 403, 'You do not have permission to renew this domain.');

        $years = (int) $request->validated()['years'];

        $domainPrice = $renewalService->resolveDomainPrice($domain);

        if (! $domainPrice) {
            return $this->respondRenewalError($request, 'Pricing information not available for this domain.', 404);
        }

        $domain->setRelation('domainPrice', $domainPrice);

        $stripeValidation = $renewalService->validateStripeMinimumAmountForRenewal($domainPrice, $years);

        if (! $stripeValidation['valid']) {
            return $this->respondRenewalError($request, $stripeValidation['message'], 422, $stripeValidation);
        }

        $priceData = $renewalService->getRenewalPrice($domain, $years);
        $renewalPricePerYear = $priceData['unit_price'];
        $totalPrice = $priceData['total_price'];
        $currency = $priceData['currency'];

        $tld = $domainPrice->tld ? Str::ltrim($domainPrice->tld, '.') : $this->extractTldFallback($domain->name);
        $tld = $tld ? '.'.$tld : null;

        // Create unique cart ID for renewal (consistent with CartController)
        $cartId = 'renewal-'.$domain->id;

        // Check if already in cart
        if (Cart::get($cartId)) {
            return $this->respondRenewalError($request, 'This domain renewal is already in your cart.', 400);
        }

        // Add to cart
        Cart::add([
            'id' => $cartId,
            'name' => $domain->name.' (Renewal)',
            'price' => $renewalPricePerYear,
            'quantity' => $years,
            'attributes' => [
                'type' => 'renewal',
                'domain_id' => $domain->id,
                'domain_name' => $domain->name,
                'years' => $years,
                'current_expiry' => $domain->expires_at?->toDateString(),
                'tld' => $tld,
                'currency' => $currency,
                'unit_price' => $renewalPricePerYear,
                'total_price' => $totalPrice,
                'added_at' => now()->timestamp,
            ],
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => sprintf('Domain %s added to cart for %s year(s) renewal.', $domain->name, $years),
            ]);
        }

        return to_route('checkout.index')
            ->with('success', sprintf('Domain %s added to cart for %s year(s) renewal.', $domain->name, $years));
    }

    private function respondRenewalError(AddDomainRenewalToCartRequest $request, string $message, int $status, array $payload = []): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'details' => array_diff_key($payload, array_flip(['valid'])),
            ], $status);
        }

        return back()
            ->withInput()
            ->with('error', $message);
    }

    private function extractTldFallback(string $domain): ?string
    {
        $parts = explode('.', $domain);

        if (count($parts) < 2) {
            return null;
        }

        return end($parts) ?: null;
    }
}
