<?php

declare(strict_types=1);

namespace App\Livewire;

use Cknow\Money\Money;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Livewire\Component;

final class NavbarComponent extends Component
{
    protected $listeners = ['refreshCart' => '$refresh'];

    public function getCartItemsCountProperty(): int
    {
        return Cart::getContent()->count();
    }

    public function getFormattedTotalProperty(): string
    {
        return Money::RWF(Cart::getTotal())->format();
    }

    public function render(): object
    {
        return view('livewire.navbar-component');
    }
}
