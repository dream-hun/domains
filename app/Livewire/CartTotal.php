<?php

declare(strict_types=1);

namespace App\Livewire;

use Cknow\Money\Money;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Illuminate\Contracts\View\View;
use Livewire\Component;

final class CartTotal extends Component
{
    protected $listeners = ['refreshCart' => '$refresh'];

    public function getFormattedTotalProperty(): string
    {
        return Money::RWF(Cart::getTotal())->format();
    }

    public function getCartItemsCountProperty(): int
    {
        return Cart::getContent()->count();
    }

    public function render(): View
    {
        return view('livewire.cart-total');
    }
}
