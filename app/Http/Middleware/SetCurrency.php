<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Currency;
use App\Services\GeolocationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class SetCurrency
{
    public function __construct(
        private GeolocationService $geolocationService
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->has('currency')) {
            $this->setCurrencyIfValid($request->get('currency'));
        }

        if (! session()->has('selected_currency')) {
            $currencyCode = $this->getCurrencyFromUserPreference($request)
                ?? $this->getCurrencyFromGeolocation();

            $this->setCurrencyIfValid($currencyCode);
        }

        return $next($request);
    }

    /**
     * Get currency from authenticated user's address preferred currency.
     */
    private function getCurrencyFromUserPreference(Request $request): ?string
    {
        $user = $request->user();

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
    private function getCurrencyFromGeolocation(): string
    {
        return match ($this->geolocationService->isUserFromRwanda()) {
            true => 'RWF',
            false => 'USD',
        };
    }

    /**
     * Set session currency if the currency code is valid and active.
     */
    private function setCurrencyIfValid(?string $currencyCode): void
    {
        if (! $currencyCode) {
            return;
        }

        $currency = Currency::getActiveCurrencies()->firstWhere('code', $currencyCode);

        if ($currency) {
            session(['selected_currency' => $currency->code]);
        }
    }
}
