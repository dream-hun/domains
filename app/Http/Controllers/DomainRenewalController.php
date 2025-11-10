<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\AddDomainRenewalToCartRequest;
use App\Models\Domain;
use App\Models\DomainPrice;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class DomainRenewalController extends Controller
{
    /**
     * Show the renewal page for a domain
     */
    public function show(Domain $domain): View
    {
        // Ensure the user owns this domain
        abort_if($domain->owner_id !== auth()->id() && ! auth()->user()->isAdmin(), 403, 'You do not have permission to renew this domain.');

        // Get the TLD and pricing information
        $tld = $this->extractTld($domain->name);
        $domainPrice = DomainPrice::query()->where('tld', '.'.$tld)->first();

        abort_unless($domainPrice, 404, 'Pricing information not available for this domain.');

        return view('domains.renew', [
            'domain' => $domain,
            'domainPrice' => $domainPrice,
            'renewalPrice' => $domainPrice->renewal_price / 100, // Convert cents to dollars
            'currency' => $domainPrice->type->value === 'Local' ? 'RWF' : 'USD',
        ]);
    }

    /**
     * Add a domain renewal to the cart
     */
    public function addToCart(AddDomainRenewalToCartRequest $request, Domain $domain): RedirectResponse
    {
        // Ensure the user owns this domain
        abort_if($domain->owner_id !== auth()->id() && ! auth()->user()->isAdmin(), 403, 'You do not have permission to renew this domain.');

        $years = $request->validated()['years'];

        // Get the TLD and pricing information
        $tld = $this->extractTld($domain->name);
        $domainPrice = DomainPrice::query()->where('tld', '.'.$tld)->first();

        if (! $domainPrice) {
            return back()
                ->with('error', 'Pricing information not available for this domain.');
        }

        // Calculate price (price is stored in cents)
        $renewalPricePerYear = $domainPrice->renewal_price / 100;
        $currency = $domainPrice->type->value === 'Local' ? 'RWF' : 'USD';

        // Clear existing cart items to ensure single item checkout
        // You can modify this to allow multiple renewals in one cart
        Cart::clear();

        // Add to cart
        Cart::add([
            'id' => $domain->id,
            'name' => $domain->name,
            'price' => $renewalPricePerYear,
            'quantity' => $years,
            'attributes' => [
                'type' => 'renewal',
                'years' => $years,
                'current_expiry' => $domain->expires_at->toDateString(),
                'tld' => $tld,
                'currency' => $currency,
            ],
        ]);

        return to_route('checkout.index')
            ->with('success', sprintf('Domain %s added to cart for %s year(s) renewal.', $domain->name, $years));
    }

    /**
     * Extract TLD from domain name
     */
    private function extractTld(string $domain): string
    {
        $parts = explode('.', $domain);

        return end($parts) ?: '';
    }
}
