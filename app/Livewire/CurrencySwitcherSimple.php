<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Currency;
use App\Services\CurrencyService;
use App\Services\GeolocationService;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Log;

final class CurrencySwitcherSimple extends Component
{
    public string $selectedCurrency = '';

    public bool $showDropdown = false;

    public function mount(CurrencyService $currencyService, GeolocationService $geolocationService): void
    {
        // If session already has a currency, use it
        if (session()->has('selected_currency')) {
            $this->selectedCurrency = session('selected_currency');

            Log::info('Using existing session currency', [
                'currency' => $this->selectedCurrency,
            ]);

            return;
        }

        // No session currency, initialize from geolocation
        $isFromRwanda = $geolocationService->isUserFromRwanda();
        $userCountry = $geolocationService->getUserCountryCode();

        Log::info('Initializing currency from geolocation', [
            'ip' => request()->ip(),
            'country_code' => $userCountry,
            'is_from_rwanda' => $isFromRwanda,
        ]);

        if ($isFromRwanda) {
            $this->selectedCurrency = 'RWF';
        } else {
            $this->selectedCurrency = 'USD';
        }

        session(['selected_currency' => $this->selectedCurrency]);
    }

    public function selectCurrency(string $currencyCode): void
    {
        Log::info('Currency change requested', [
            'currency_code' => $currencyCode,
        ]);

        $currency = Currency::where('code', $currencyCode)
            ->where('is_active', true)
            ->first();

        if (! $currency) {
            Log::error('Currency not found or inactive', [
                'currency_code' => $currencyCode,
            ]);

            return;
        }

        // Update the selected currency
        $this->selectedCurrency = $currency->code;

        // Close the dropdown
        $this->showDropdown = false;

        // Store in session
        session(['selected_currency' => $currency->code]);

        Log::info('Dispatching currency change events', [
            'currency_code' => $currency->code,
        ]);

        // Dispatch events to update all listening components
        $this->dispatch('currency-changed', currency: $currency->code);
        $this->dispatch('currencyChanged', currency: $currency->code);
        $this->dispatch('refreshCart'); // Refresh cart components
    }

    public function toggleDropdown(): void
    {
        $this->showDropdown = ! $this->showDropdown;
    }

    public function render(CurrencyService $currencyService): Factory|View|\Illuminate\View\View
    {
        $availableCurrencies = [];

        foreach ($currencyService->getActiveCurrencies()->whereIn('code', ['USD', 'RWF']) as $currency) {
            $availableCurrencies[$currency->code] = [
                'name' => $currency->name,
                'symbol' => $currency->symbol,
                'code' => $currency->code,
            ];
        }

        return view('livewire.currency-switcher-simple', [
            'availableCurrencies' => $availableCurrencies,
        ]);
    }
}
