<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Traits\HasCurrency;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

final class NavbarComponent extends Component
{
    use HasCurrency;

    public string $selectedCurrency = '';

    public string $formattedTotal = '';

    public float $discountAmount = 0;

    protected $listeners = [
        'refreshCart' => 'refreshCart',
        'currency-changed' => 'handleCurrencyChanged',
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
        $this->discountAmount = $this->calculateDiscount($subtotal);
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

    private function calculateDiscount(float $subtotal): float
    {
        if (! session()->has('coupon')) {
            return 0;
        }

        $couponData = session('coupon');
        $couponCurrency = $couponData['currency'] ?? 'USD';
        $discountAmount = $couponData['discount_amount'] ?? 0;

        // Convert discount to current currency if different
        if ($couponCurrency !== $this->selectedCurrency) {
            try {
                $discountAmount = $this->convertCurrency(
                    $discountAmount,
                    $couponCurrency,
                    $this->selectedCurrency
                );
            } catch (Exception) {
                // Fallback to recalculating discount
                $type = $couponData['type'] ?? 'percentage';
                $value = $couponData['value'] ?? 0;

                if ($type === 'percentage') {
                    $discountAmount = $subtotal * ($value / 100);
                } elseif ($type === 'fixed') {
                    // Convert fixed amount to current currency
                    try {
                        $discountAmount = $this->convertCurrency(
                            $value,
                            $couponCurrency,
                            $this->selectedCurrency
                        );
                    } catch (Exception) {
                        $discountAmount = $value;
                    }
                }
            }
        }

        // Ensure discount doesn't exceed subtotal
        return min($discountAmount, $subtotal);
    }

    private function updateFormattedTotal(): void
    {
        $this->formattedTotal = $this->calculateTotal();
    }
}
