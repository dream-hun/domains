<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Services\RenewalService;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Log;

final class CartController extends Controller
{
    public function __invoke(): Redirector|RedirectResponse|Factory|View
    {
        if (Cart::isEmpty()) {
            return to_route('domains');
        }

        return view('carts.index');
    }

    /**
     * Add domain renewal to cart via AJAX
     */
    public function addRenewalToCart(Request $request, Domain $domain): JsonResponse
    {
        try {
            $years = (int) $request->input('years', 1);
            $user = auth()->user();

            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'You must be logged in to renew a domain',
                ], 401);
            }

            // Validate ownership
            if ($domain->owner_id !== $user->id && ! $user->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not own this domain',
                ], 403);
            }

            // Validate domain can be renewed
            $renewalService = app(RenewalService::class);
            $domainPrice = $renewalService->resolveDomainPrice($domain);

            if (! $domainPrice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pricing information not available for this domain',
                ], 404);
            }

            $minimumValidation = $renewalService->validateStripeMinimumAmountForRenewal($domain, $domainPrice, $years);

            if (! $minimumValidation['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => $minimumValidation['message'],
                    'min_years' => $minimumValidation['min_years'] ?? null,
                ], 422);
            }

            $domain->setRelation('domainPrice', $domainPrice);

            $canRenew = $renewalService->canRenewDomain($domain, $user);

            if (! $canRenew['can_renew']) {
                return response()->json([
                    'success' => false,
                    'message' => $canRenew['reason'] ?? 'Cannot renew this domain',
                ], 400);
            }

            // Get renewal price
            $priceData = $renewalService->getRenewalPrice($domain, $years);
            $price = $priceData['unit_price'];
            $totalPrice = $priceData['total_price'];
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
                    'unit_price' => $price,
                    'total_price' => $totalPrice,
                    'added_at' => now()->timestamp,
                ],
            ]);

            return response()->json([
                'success' => true,
                'message' => sprintf('Domain renewal for %s added to cart for %s year(s)', $domain->name, $years),
            ]);

        } catch (Exception $exception) {
            Log::error('Failed to add renewal to cart', [
                'domain_id' => $domain->id,
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to add renewal to cart: '.$exception->getMessage(),
            ], 500);
        }
    }
}
