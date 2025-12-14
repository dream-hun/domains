<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Darryldecode\Cart\Facades\CartFacade as Cart;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;

final class CartController extends Controller
{
    public function __invoke(): Redirector|RedirectResponse|Factory|View
    {
        if (Cart::isEmpty()) {
            return to_route('domains');
        }

        return view('carts.index');
    }
}
