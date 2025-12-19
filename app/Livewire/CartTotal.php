<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Currency;
use App\Services\CartPriceConverter;
use App\Services\CurrencyService;
use App\Traits\HasCurrency;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Component;

final class CartTotal extends Component
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

    public function currencyChanged(): void
    {
        $this->updatedSelectedCurrency();
    }

    public function updatedSelectedCurrency(): void
    {
        $currency = Currency::query()->where('code', $this->selectedCurrency)
            ->where('is_active', true)
            ->first();

        if ($currency) {
            session(['selected_currency' => $currency->code]);

            $this->updateFormattedTotal();

            $this->dispatch('currency-changed', currency: $currency->code);
            $this->dispatch('currencyChanged', $currency->code);
        }
    }

    public function handleCurrencyChanged(string $currency): void
    {
        Log::info('CartTotal received currency change', [
            'new_currency' => $currency,
            'old_currency' => $this->selectedCurrency,
        ]);

        $this->selectedCurrency = $currency;
        $this->updateFormattedTotal();

        Log::info('CartTotal updated total', [
            'formatted_total' => $this->formattedTotal,
        ]);
    }

    public function calculateTotal(): string
    {
        $cartItems = Cart::getContent();

        try {
            $cartPriceConverter = app(CartPriceConverter::class);
            $subtotal = $cartPriceConverter->calculateCartSubtotal($cartItems, $this->selectedCurrency);
        } catch (Exception $exception) {
            Log::error('Failed to calculate cart total in CartTotal', [
                'currency' => $this->selectedCurrency,
                'error' => $exception->getMessage(),
            ]);

            // Fallback to 0 if conversion fails
            $subtotal = 0;
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

    public function render(CurrencyService $currencyService): View
    {
        return view('livewire.cart-total', [
            'currencies' => $currencyService->getActiveCurrencies(),
            'currentCurrency' => $currencyService->getCurrency($this->selectedCurrency) ?? $currencyService->getUserCurrency(),
        ]);
    }

    /**
     * Get the number of months for a billing cycle
     */
    private function getBillingCycleMonths(string $billingCycle): int
    {
        return match ($billingCycle) {
            'monthly' => 1,
            'quarterly' => 3,
            'semi-annually' => 6,
            'annually' => 12,
            'biennially' => 24,
            'triennially' => 36,
            default => 1,
        };
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
