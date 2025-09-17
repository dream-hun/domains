<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Traits\HasCurrency;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Exception;
use Illuminate\Contracts\View\View;
use Livewire\Component;

final class DomainCartButton extends Component
{
    use HasCurrency;

    public $domain;

    public $price;

    public $available;

    public $domainPrice; // DomainPrice model instance

    public $currency;

    protected $listeners = ['refreshCart' => '$refresh', 'currency-changed' => 'updateCurrency', 'currencyChanged' => 'updateCurrency'];

    public function mount($domain, $price, $available = true, $domainPrice = null, $currency = null): void
    {
        $this->domain = $domain;
        $this->price = $price;
        $this->available = $available;
        $this->domainPrice = $domainPrice;
        $this->currency = $currency ?? $this->getUserCurrency()->code;
    }

    public function updateCurrency($newCurrency): void
    {
        $this->currency = $newCurrency;

        // Update price if we have the domain price model
        if ($this->domainPrice) {
            $this->price = $this->domainPrice->getFormattedPrice('register_price', $this->currency);
        }
    }

    public function getIsInCartProperty(): bool
    {
        return Cart::get($this->domain) !== null;
    }

    public function addToCart(): void
    {
        try {
            // Get numeric price in the selected currency
            $numericPrice = $this->domainPrice
                ? $this->domainPrice->getPriceInCurrency('register_price', $this->currency)
                : (float) preg_replace('/[^\d.]/', '', $this->price);

            Cart::add([
                'id' => $this->domain,
                'name' => $this->domain,
                'price' => $numericPrice,
                'quantity' => 1,
                'attributes' => [
                    'type' => 'domain',
                    'currency' => $this->currency,
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
