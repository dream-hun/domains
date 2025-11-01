<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Services\RenewalService;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CartController extends Controller
{
    public function __invoke()
    {
        if (Cart::isEmpty()) {
            return redirect()->route('domains');
        }

        return view('carts.index');
    }

    /**
     * Add domain renewal to cart via AJAX
     */
    public function addRenewalToCart(Request $request, Domain $domain): JsonResponse
    {
        try {
            $years = $request->input('years', 1);

            // Validate ownership
            if ($domain->owner_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not own this domain',
                ], 403);
            }

            // Validate domain can be renewed
            $renewalService = app(RenewalService::class);
            $canRenew = $renewalService->canRenewDomain($domain, auth()->id());

            if (! $canRenew['can_renew']) {
                return response()->json([
                    'success' => false,
                    'message' => $canRenew['reason'] ?? 'Cannot renew this domain',
                ], 400);
            }

            // Get renewal price
            $priceData = $renewalService->getRenewalPrice($domain, $years);
            $price = $priceData['price'];
            $currency = $priceData['currency'];

            // Create unique cart ID for renewal
            $cartId = 'renewal-'.$domain->id;

            // Check if already in cart
            if (Cart::get($cartId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'This domain renewal is already in your cart',
                ], 400);
            }

            // Add to cart
            Cart::add([
                'id' => $cartId,
                'name' => $domain->name.' (Renewal)',
                'price' => $price,
                'quantity' => $years,
                'attributes' => [
                    'type' => 'renewal',
                    'domain_id' => $domain->id,
                    'domain_name' => $domain->name,
                    'current_expiry' => $domain->expires_at?->format('Y-m-d'),
                    'tld' => $domain->domainPrice->tld,
                    'currency' => $currency,
                    'added_at' => now()->timestamp,
                ],
            ]);

            return response()->json([
                'success' => true,
                'message' => "Domain renewal for {$domain->name} added to cart for {$years} year(s)",
            ]);

        } catch (Exception $e) {
            \Log::error('Failed to add renewal to cart', [
                'domain_id' => $domain->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to add renewal to cart: '.$e->getMessage(),
            ], 500);
        }
    }
}
