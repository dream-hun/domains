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
            $currencyCode = $request->get('currency');
            $currency = Currency::getActiveCurrencies()->firstWhere('code', $currencyCode);

            if ($currency) {
                session(['selected_currency' => $currency->code]);
            }
        }

        if (! session()->has('selected_currency')) {
            $currencyCode = $this->geolocationService->isUserFromRwanda() ? 'RWF' : 'USD';
            $currency = Currency::getActiveCurrencies()->firstWhere('code', $currencyCode);

            if ($currency) {
                session(['selected_currency' => $currency->code]);
            }
        }

        return $next($request);
    }
}
