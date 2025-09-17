<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Currency;
use App\Services\CurrencyService;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Livewire\Component;

final class CurrencySwitcher extends Component
{
    public string $selectedCurrency = '';

    public function mount(CurrencyService $currencyService): void
    {
        $this->selectedCurrency = $currencyService->getUserCurrency()->code;
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

            $this->dispatch('currency-changed', currency: $currency->code);
            $this->dispatch('currencyChanged', $currency->code); // For backward compatibility
            $this->dispatch('$refresh');
        }
    }

    public function render(CurrencyService $currencyService): Factory|View|\Illuminate\View\View
    {
        return view('livewire.currency-switcher', [
            'currencies' => $currencyService->getActiveCurrencies(),
            'currentCurrency' => $currencyService->getUserCurrency(),
        ]);
    }
}
