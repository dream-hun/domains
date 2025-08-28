<?php

declare(strict_types=1);

namespace App\Livewire;

use Darryldecode\Cart\Facades\CartFacade as Cart;
use Exception;
use Illuminate\Contracts\View\View;
use Livewire\Component;

final class DomainCartButton extends Component
{
    public $domain;

    public $price;

    public $available;

    protected $listeners = ['refreshCart' => '$refresh'];

    public function mount($domain, $price, $available = true): void
    {
        $this->domain = $domain;
        $this->price = $price;
        $this->available = $available;
    }

    public function getIsInCartProperty(): bool
    {
        return Cart::get($this->domain) !== null;
    }

    public function addToCart(): void
    {
        try {
            // Convert price string to numeric value (remove currency symbols)
            $numericPrice = (float) preg_replace('/[^\d.]/', '', $this->price);

            Cart::add([
                'id' => $this->domain,
                'name' => $this->domain,
                'price' => $numericPrice,
                'quantity' => 1,
                'attributes' => [
                    'type' => 'domain',
                    'added_at' => now()->timestamp,
                ],
            ]);

            $this->dispatch('refreshCart');

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Domain added to cart successfully',
            ]);
        } catch (Exception) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Failed to add domain to cart',
            ]);
        }
    }

    public function removeFromCart(): void
    {
        try {
            Cart::remove($this->domain);
            $this->dispatch('refreshCart');

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Domain removed from cart',
            ]);
        } catch (Exception) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Failed to remove domain from cart',
            ]);
        }
    }

    public function render(): View
    {
        return view('livewire.domain-cart-button');
    }
}
