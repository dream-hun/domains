<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Services\CurrencyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CurrencyController extends Controller
{
    public function __construct(
        private readonly CurrencyService $currencyService
    ) {}

    /**
     * Get all active currencies
     */
    public function index(): JsonResponse
    {
        $currencies = $this->currencyService->getActiveCurrencies();
        $currentCurrency = $this->currencyService->getUserCurrency();

        return response()->json([
            'currencies' => $currencies,
            'current' => $currentCurrency,
        ]);
    }

    /**
     * Switch user's currency
     */
    public function switch(Request $request): JsonResponse
    {
        $request->validate([
            'currency' => 'required|string|size:3|exists:currencies,code',
        ]);

        $currency = Currency::where('code', $request->currency)
            ->where('is_active', true)
            ->first();

        if (! $currency) {
            return response()->json([
                'success' => false,
                'message' => 'Currency not found or inactive',
            ], 404);
        }

        // Set in session
        session(['selected_currency' => $currency->code]);

        return response()->json([
            'success' => true,
            'currency' => $currency,
            'message' => "Currency switched to {$currency->name}",
        ]);
    }
}
