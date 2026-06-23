<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Traits\CalculatesCartDiscount;
use App\Traits\HasCurrency;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

final class NavbarComponent extends Component
{
    use CalculatesCartDiscount;
    use HasCurrency;

    public string $selectedCurrency = '';

    public string $formattedTotal = '';

    public float $discountAmount = 0;

    protected $listeners = [
        'refreshCart' => 'refreshCart',
        'currencyChanged' => 'handleCurrencyChanged',
        'couponApplied' => 'refreshCart',
        'couponRemoved' => 'refreshCart',
    ];

    public function mount(): void
    {
        $this->selectedCurrency = $this->getUserCurrency()->code;
        $this->updateFormattedTotal();
    }

    public function refreshCart(): void
    {
        $this->updateFormattedTotal();
    }

    public function handleCurrencyChanged(string $currency): void
    {
        $this->selectedCurrency = $currency;
        $this->updateFormattedTotal();
    }

    public function calculateTotal(): string
    {
        $cartItems = Cart::getContent();
        $subtotal = 0;

        foreach ($cartItems as $item) {
            $itemCurrency = $item->attributes->currency ?? 'USD';

            if ($itemCurrency !== $this->selectedCurrency) {
                try {
                    $convertedPrice = $this->convertCurrency(
                        $item->price,
                        $itemCurrency,
                        $this->selectedCurrency
                    );
                    $subtotal += $convertedPrice * $item->quantity;
                } catch (Exception) {
                    $subtotal += $item->price * $item->quantity;
                }
            } else {
                $subtotal += $item->price * $item->quantity;
            }
        }

        // Apply discount from session if coupon is applied
        $this->discountAmount = $this->calculateSessionDiscount($subtotal, $this->selectedCurrency);
        $total = max(0, $subtotal - $this->discountAmount);

        return $this->formatCurrency($total, $this->selectedCurrency);
    }

    #[Computed]
    public function cartItemsCount(): int
    {
        return Cart::getContent()->count();
    }

    public function render(): Factory|View
    {
        return view('livewire.navbar-component');
    }

    private function updateFormattedTotal(): void
    {
        $this->formattedTotal = $this->calculateTotal();
    }
}
