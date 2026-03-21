<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Tld;
use App\Traits\HasCurrency;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Exception;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

final class DomainCartButton extends Component
{
    use HasCurrency;

    public string $domain;

    public string $price;

    public bool $available;

    public mixed $domainPrice = null; // Tld model instance

    public string $currency;

    protected $listeners = ['refreshCart' => '$refresh', 'currency-changed' => 'updateCurrency', 'currencyChanged' => 'updateCurrency'];

    public function mount(string $domain, mixed $price, bool $available = true, mixed $domainPrice = null, ?string $currency = null, ?int $tldId = null): void
    {
        $this->domain = $domain;
        $this->available = $available;
        $this->domainPrice = $domainPrice;
        $this->currency = $currency ?? $this->getUserCurrency()->code;

        if ($this->domainPrice === null && $tldId !== null && ($price === null || $price === '')) {
            $this->domainPrice = Tld::query()
                ->with(['tldPricings' => fn (mixed $q) => $q->where('is_current', true)->with('currency')])
                ->find($tldId);
        }

        $this->price = $this->domainPrice
            ? $this->domainPrice->getFormattedPriceWithFallback('register_price', $this->currency)
            : (is_string($price) ? $price : (string) $price);
    }

    public function updateCurrency(string $currency): void
    {
        $this->currency = $currency;
        if ($this->domainPrice) {
            $this->price = $this->domainPrice->getFormattedPriceWithFallback('register_price', $this->currency);
        }
    }

    #[Computed]
    public function isInCart(): bool
    {
        return Cart::get($this->domain) !== null;
    }

    public function addToCart(): void
    {
        try {
            if ($this->domainPrice) {
                $display = $this->domainPrice->getDisplayPriceForCurrency($this->currency, 'register_price');
                $numericPrice = $display['amount'];
                $itemCurrency = $display['currency_code'];
            } else {
                $numericPrice = (float) preg_replace('/[^\d.]/', '', $this->price);
                $itemCurrency = $this->currency;
            }

            Cart::add([
                'id' => $this->domain,
                'name' => $this->domain,
                'price' => $numericPrice,
                'quantity' => 1,
                'attributes' => [
                    'type' => 'domain',
                    'domain_name' => $this->domain,
                    'currency' => $itemCurrency,
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
