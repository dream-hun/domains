<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Darryldecode\Cart\Facades\CartFacade as Cart;
use Illuminate\Http\RedirectResponse;

final class SmartCheckoutController extends Controller
{
    /**
     * Route to the appropriate checkout flow based on cart contents
     */
    public function index(): RedirectResponse
    {
        $cartItems = Cart::getContent();

        if ($cartItems->isEmpty()) {
            return redirect()
                ->route('cart.index')
                ->with('error', 'Your cart is empty.');
        }

        // Check if cart has ONLY renewals
        $hasOnlyRenewals = $cartItems->every(function ($item) {
            return ($item->attributes->type ?? 'registration') === 'renewal';
        });

        // If only renewals, go directly to renewal checkout (no contact needed)
        if ($hasOnlyRenewals) {
            return redirect()->route('checkout.renewal');
        }

        // Otherwise, go to full checkout wizard with contact selection
        return redirect()->route('checkout.wizard');
    }
}

