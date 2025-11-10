<?php

declare(strict_types=1);

namespace App\Actions\Currency;

use App\Models\Currency;
use App\Services\ExchangeRateClient;
use Illuminate\Support\Facades\Log;

final readonly class UpdateExchangeRatesAction
{
    public function __construct(
        private ExchangeRateClient $client,
        private ProcessExchangeRatesAction $processor
    ) {}

    public function handle(): bool
    {
        Log::info('Starting exchange rate update');

        $baseCurrency = Currency::getBaseCurrency();

        $rates = $this->client->fetchRates($baseCurrency->code);

        if ($rates === null) {
            Log::error('Failed to fetch exchange rates from API');

            return false;
        }

        $success = $this->processor->handle($rates);

        if ($success) {
            Log::info('Exchange rate update completed successfully');
        } else {
            Log::error('Failed to process exchange rates');
        }

        return $success;
    }
}
