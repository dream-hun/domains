<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Currency;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SetCurrency
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if currency is being changed via request
        if ($request->has('currency')) {
            $currencyCode = $request->get('currency');
            $currency = Currency::query()->where('code', $currencyCode)->where('is_active', true)->first();

            if ($currency) {
                session(['selected_currency' => $currency->code]);
            }
        }

        return $next($request);
    }
}
