<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Currency;
use App\Services\GeolocationService;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

final class CurrencySwitcher extends Component
{
    public string $selectedCurrency = '';

    public bool $showDropdown = false;

    public function mount(GeolocationService $geolocationService): void
    {

        if (session()->has('selected_currency')) {
            $sessionCurrency = session('selected_currency');

            if (is_string($sessionCurrency)) {
                $this->selectedCurrency = $sessionCurrency;

                Log::info('Using existing session currency', [
                    'currency' => $this->selectedCurrency,
                ]);

                return;
            }

            session()->forget('selected_currency');
        }

        $isFromRwanda = $geolocationService->isUserFromRwanda();
        $userCountry = $geolocationService->getUserCountryCode();

        Log::info('Initializing currency from geolocation', [
            'ip' => request()->ip(),
            'country_code' => $userCountry,
            'is_from_rwanda' => $isFromRwanda,
        ]);

        $this->selectedCurrency = $isFromRwanda ? 'RWF' : 'USD';

        session(['selected_currency' => $this->selectedCurrency]);
    }

    public function selectCurrency(string $currencyCode): void
    {
        Log::info('Currency change requested', [
            'currency_code' => $currencyCode,
        ]);

        $currency = Currency::getActiveCurrencies()->firstWhere('code', $currencyCode);

        if (! $currency) {
            Log::error('Currency not found or inactive', [
                'currency_code' => $currencyCode,
            ]);

            return;
        }

        $this->selectedCurrency = $currency->code;
        $this->showDropdown = false;

        session(['selected_currency' => $currency->code]);

        Log::info('Dispatching currency change events', [
            'currency_code' => $currency->code,
        ]);

        $this->dispatch('currency-changed', currency: $currency->code);
        $this->dispatch('currencyChanged', currency: $currency->code);
        $this->dispatch('refreshCart');
    }

    public function toggleDropdown(): void
    {
        $this->showDropdown = ! $this->showDropdown;
    }

    public function render(): Factory|View|\Illuminate\View\View
    {
        $currencies = Currency::getActiveCurrencies();

        $availableCurrencies = [];
        foreach ($currencies as $currency) {
            $availableCurrencies[$currency->code] = [
                'name' => $currency->name,
                'symbol' => $currency->symbol,
                'code' => $currency->code,
            ];
        }

        return view('livewire.currency-switcher', [
            'availableCurrencies' => $availableCurrencies,
        ]);
    }
}
