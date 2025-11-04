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

        
        $hasOnlyRenewals = $cartItems->every(function ($item) {
            return ($item->attributes->type ?? 'registration') === 'renewal';
        });

        
        if ($hasOnlyRenewals) {
            return redirect()->route('checkout.renewal');
        }

        return redirect()->route('checkout.wizard');
    }
}

