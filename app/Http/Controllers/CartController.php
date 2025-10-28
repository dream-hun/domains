<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Darryldecode\Cart\Facades\CartFacade as Cart;

final class CartController extends Controller
{
    public function __invoke()
    {
        if (Cart::isEmpty()) {
            return redirect()->route('domains');
        }

        return view('carts.index');
    }
}
