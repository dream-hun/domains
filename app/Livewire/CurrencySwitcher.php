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
        $userPreference = $this->getCurrencyFromUserPreference();

        if ($userPreference !== null) {
            $this->setCurrency($userPreference);

            Log::info('Using authenticated user preferred currency', [
                'currency' => $userPreference,
            ]);

            return;
        }

        $sessionCurrency = session('selected_currency');

        if (is_string($sessionCurrency) && $this->isValidCurrency($sessionCurrency)) {
            $this->setCurrency($sessionCurrency);

            Log::info('Using existing session currency', [
                'currency' => $sessionCurrency,
            ]);

            return;
        }

        if ($sessionCurrency !== null) {
            session()->forget('selected_currency');
        }

        $currencyCode = $this->getCurrencyFromGeolocation($geolocationService);
        $this->setCurrency($currencyCode);
    }

    public function selectCurrency(string $currencyCode): void
    {
        Log::info('Currency change requested', [
            'currency_code' => $currencyCode,
        ]);

        if (! $this->isValidCurrency($currencyCode)) {
            Log::error('Currency not found or inactive', [
                'currency_code' => $currencyCode,
            ]);

            return;
        }

        $this->setCurrency($currencyCode);
        $this->showDropdown = false;

        Log::info('Dispatching currency change events', [
            'currency_code' => $currencyCode,
        ]);

        $this->dispatch('currency-changed', currency: $currencyCode);
        $this->dispatch('currencyChanged', currency: $currencyCode);
        $this->dispatch('refreshCart');
    }

    public function toggleDropdown(): void
    {
        $this->showDropdown = ! $this->showDropdown;
    }

    public function render(): Factory|View|\Illuminate\View\View
    {
        $availableCurrencies = Currency::getActiveCurrencies()
            ->mapWithKeys(fn (Currency $currency) => [
                $currency->code => [
                    'name' => $currency->name,
                    'symbol' => $currency->symbol,
                    'code' => $currency->code,
                ],
            ])
            ->all();

        return view('livewire.currency-switcher', [
            'availableCurrencies' => $availableCurrencies,
        ]);
    }

    /**
     * Get currency from authenticated user's address preferred currency.
     */
    private function getCurrencyFromUserPreference(): ?string
    {
        $user = auth()->user();

        if (! $user) {
            return null;
        }

        $address = $user->address;

        if (! $address || ! $address->preferred_currency) {
            return null;
        }

        $preferredCurrency = Currency::getActiveCurrencies()
            ->firstWhere('code', $address->preferred_currency);

        return $preferredCurrency?->code;
    }

    /**
     * Get currency based on user's geolocation.
     */
    private function getCurrencyFromGeolocation(GeolocationService $geolocationService): string
    {
        $isFromRwanda = $geolocationService->isUserFromRwanda();
        $userCountry = $geolocationService->getUserCountryCode();

        Log::info('Initializing currency from geolocation', [
            'ip' => request()->ip(),
            'country_code' => $userCountry,
            'is_from_rwanda' => $isFromRwanda,
        ]);

        return match ($isFromRwanda) {
            true => 'RWF',
            false => 'USD',
        };
    }

    /**
     * Check if currency code is valid and active.
     */
    private function isValidCurrency(string $currencyCode): bool
    {
        return Currency::getActiveCurrencies()
            ->contains('code', $currencyCode);
    }

    /**
     * Set the selected currency and update session.
     */
    private function setCurrency(string $currencyCode): void
    {
        if (! $this->isValidCurrency($currencyCode)) {
            return;
        }

        $this->selectedCurrency = $currencyCode;
        session(['selected_currency' => $currencyCode]);
    }
}
