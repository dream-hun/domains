<?php

declare(strict_types=1);

namespace App\Livewire;

use Darryldecode\Cart\Facades\CartFacade as Cart;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Livewire\Component;

final class NavbarComponent extends Component
{
    protected $listeners = ['refreshCart' => '$refresh'];

    public function getCartItemsCountProperty(): int
    {
        return Cart::getContent()->count();
    }

    public function render(): Factory|View
    {
        return view('livewire.navbar-component');
    }
}
