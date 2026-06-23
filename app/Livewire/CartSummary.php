<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Helpers\CurrencyHelper;
use App\Services\CartPriceConverter;
use App\Traits\CalculatesCartDiscount;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Throwable;

final class CartSummary extends Component
{
    use CalculatesCartDiscount;

    public string $currency;

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
     * @throws Exception|Throwable
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

        $this->discountAmount = $this->calculateSessionDiscount($subtotal, $this->currency);
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
}
