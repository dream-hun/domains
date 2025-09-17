<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Currency;
use App\Services\CurrencyService;
use App\Traits\HasCurrency;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Exception;
use Illuminate\Contracts\View\View;
use Livewire\Component;

final class CartTotal extends Component
{
    use HasCurrency;

    public string $selectedCurrency = '';

    public string $formattedTotal = '';

    protected $listeners = ['refreshCart' => 'refreshCart', 'currency-changed' => 'handleCurrencyChanged'];

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
        $currency = Currency::where('code', $this->selectedCurrency)
            ->where('is_active', true)
            ->first();

        if ($currency) {
            session(['selected_currency' => $currency->code]);

            if (auth()->check()) {
                auth()->user()->update(['preferred_currency' => $currency->code]);
            }

            // Update the formatted total
            $this->updateFormattedTotal();

            // Dispatch events to update other components
            $this->dispatch('currency-changed', currency: $currency->code);
            $this->dispatch('currencyChanged', $currency->code); // For backward compatibility
        }
    }

    public function handleCurrencyChanged($currency): void
    {
        $this->selectedCurrency = $currency;
        $this->updateFormattedTotal();
    }

    public function calculateTotal(): string
    {
        $cartItems = Cart::getContent();
        $total = 0;

        foreach ($cartItems as $item) {
            $itemCurrency = $item->attributes->currency ?? 'USD';

            // Convert item price to display currency if different
            if ($itemCurrency !== $this->selectedCurrency) {
                try {
                    $convertedPrice = $this->convertCurrency(
                        $item->price,
                        $itemCurrency,
                        $this->selectedCurrency
                    );
                    $total += $convertedPrice * $item->quantity;
                } catch (Exception $e) {
                    // Fallback to original price if conversion fails
                    $total += $item->price * $item->quantity;
                }
            } else {
                $total += $item->price * $item->quantity;
            }
        }

        return $this->formatCurrency($total, $this->selectedCurrency);
    }

    public function getCartItemsCountProperty(): int
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

    private function updateFormattedTotal(): void
    {
        $this->formattedTotal = $this->calculateTotal();
    }
}
