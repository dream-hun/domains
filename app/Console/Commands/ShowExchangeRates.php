<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\CurrencyService;
use Illuminate\Console\Command;

final class ShowExchangeRates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'currency:show-rates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display current exchange rates and their update status';

    /**
     * Execute the console command.
     */
    public function handle(CurrencyService $currencyService): int
    {
        $rates = $currencyService->getCurrentRates();

        if ($rates->isEmpty()) {
            $this->error('No currencies found in the system.');

            return self::FAILURE;
        }

        $this->info('Current Exchange Rates:');
        $this->newLine();

        $tableData = [];
        foreach ($rates as $rate) {
            $lastUpdate = $rate['rate_updated_at']
                ? $rate['rate_updated_at']->format('Y-m-d H:i:s').' ('.$rate['hours_since_update'].'h ago)'
                : 'Never';
            $tableData[] = [
                $rate['code'],
                $rate['name'],
                $rate['symbol'],
                $rate['is_base'] ? 'BASE' : number_format((float) $rate['exchange_rate'], 4),
                $lastUpdate,
            ];
        }

        $this->table(
            ['Code', 'Name', 'Symbol', 'Rate', 'Last Updated'],
            $tableData
        );

        // Show staleness status
        if ($currencyService->ratesAreStale()) {
            $this->warn('⚠️  Exchange rates are stale (older than 24 hours)');
            $this->line('Run: php artisan currency:update-rates');
        } else {
            $this->info('✅ Exchange rates are fresh');
        }

        return self::SUCCESS;
    }
}
