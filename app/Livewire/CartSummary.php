<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Helpers\CurrencyHelper;
use App\Services\CartPriceConverter;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Component;

final class CartSummary extends Component
{
    public $currency;

    public float $discountAmount = 0;

    protected $listeners = [
        'refreshCart' => '$refresh',
        'currencyChanged' => 'updateCurrency',
        'couponApplied' => '$refresh',
        'couponRemoved' => '$refresh',
    ];

    public function mount(): void
    {
        $this->currency = CurrencyHelper::getUserCurrency();
    }

    public function updateCurrency(string $currency): void
    {
        $this->currency = $currency;
    }

    /**
     * @throws Exception
     */
    #[Computed]
    public function formattedTotal(): string
    {
        $cartItems = Cart::getContent();

        try {
            $cartPriceConverter = resolve(CartPriceConverter::class);
            $subtotal = $cartPriceConverter->calculateCartSubtotal($cartItems, $this->currency);
        } catch (Exception $exception) {
            Log::error('Failed to calculate cart total in CartSummary', [
                'currency' => $this->currency,
                'error' => $exception->getMessage(),
            ]);

            $subtotal = 0;
        }

        // Apply discount from session if coupon is applied
        $this->discountAmount = $this->calculateDiscount($subtotal);
        $total = max(0, $subtotal - $this->discountAmount);

        return CurrencyHelper::formatMoney($total, $this->currency);
    }

    #[Computed]
    public function cartItemsCount(): int
    {
        return Cart::getContent()->count();
    }

    public function render(): Factory|Application|View|\Illuminate\View\View
    {
        return view('livewire.cart-summary');
    }

    /**
     * Calculate discount from session coupon data
     */
    private function calculateDiscount(float $subtotal): float
    {
        if (! session()->has('coupon')) {
            return 0;
        }

        $couponData = session('coupon');
        $couponCurrency = $couponData['currency'] ?? 'USD';
        $discountAmount = $couponData['discount_amount'] ?? 0;

        // Convert discount to current currency if different
        if ($couponCurrency !== $this->currency) {
            try {
                $discountAmount = CurrencyHelper::convert(
                    $discountAmount,
                    $couponCurrency,
                    $this->currency
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
                        $discountAmount = CurrencyHelper::convert(
                            $value,
                            $couponCurrency,
                            $this->currency
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
}
