<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Darryldecode\Cart\Facades\CartFacade as Cart;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class SmartCheckoutController extends Controller
{
    /**
     * Route to the appropriate checkout flow based on cart contents
     */
    public function index(): View|RedirectResponse
    {
        $cartItems = Cart::getContent();

        if ($cartItems->isEmpty()) {
            return to_route('dashboard')
                ->with('error', 'Your cart is empty.');
        }

        return view('checkout.wizard');
    }
}
