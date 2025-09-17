<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\CurrencyService;
use Illuminate\Console\Command;

final class UpdateExchangeRates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'currency:update-rates {--force : Force update even if rates are fresh}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update exchange rates from external API';

    /**
     * Execute the console command.
     */
    public function handle(CurrencyService $currencyService): int
    {
        $this->info('Checking exchange rates...');

        $force = $this->option('force');

        if ($force) {
            $this->info('Force update requested, updating rates...');
            $success = $currencyService->updateExchangeRates();
        } else {
            $this->info('Checking if rates need updating...');
            $success = $currencyService->updateExchangeRatesIfStale();
        }

        if ($success) {
            $this->info('Exchange rates are up to date!');

            return self::SUCCESS;
        }

        $this->error('Failed to update exchange rates. Check logs for details.');
        $this->line('You can try running with --force flag for immediate retry.');

        return self::FAILURE;
    }
}
