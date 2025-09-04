<?php

declare(strict_types=1);

namespace App\Http\Controllers;

final class CartController extends Controller
{
    public function __invoke(): \Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory
    {
        return view('carts.index');
    }
}
