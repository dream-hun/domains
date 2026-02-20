<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\ActivateHostingPlanPriceJob;
use App\Models\HostingPlanPrice;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;

final class ActivateEffectivePricesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hosting-prices:activate-effective';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Activate hosting plan prices whose effective date has arrived';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $today = Date::today();

        $this->info('Checking for prices with effective dates that have arrived...');

        // Find prices where effective_date <= today() and is_current = false
        $effectivePrices = HostingPlanPrice::query()
            ->where('effective_date', '<=', $today->toDateString())
            ->where('is_current', false)
            ->where('status', 'active')
            ->get();

        if ($effectivePrices->isEmpty()) {
            $this->info('No prices found that need activation.');

            return self::SUCCESS;
        }

        $this->info(sprintf('Found %d price(s) that need activation.', $effectivePrices->count()));

        $dispatched = 0;
        $skipped = 0;

        foreach ($effectivePrices as $price) {
            if ($this->shouldSkipPrice($price, $today)) {
                $this->line(sprintf(
                    'Skipping price %s - another price with later effective date exists',
                    $price->uuid
                ));
                $skipped++;

                continue;
            }

            try {
                dispatch(new ActivateHostingPlanPriceJob($price->uuid));
                $dispatched++;

                $this->line(sprintf(
                    'Dispatched activation job for price %s (Plan: %d, Currency: %d, Cycle: %s)',
                    $price->uuid,
                    $price->hosting_plan_id,
                    $price->currency_id,
                    $price->billing_cycle
                ));

            } catch (Exception $exception) {
                Log::error('ActivateEffectivePricesCommand: Failed to dispatch job', [
                    'price_uuid' => $price->uuid,
                    'error' => $exception->getMessage(),
                ]);

                $this->error(sprintf(
                    'Failed to dispatch job for price %s: %s',
                    $price->uuid,
                    $exception->getMessage()
                ));
            }
        }

        $this->newLine();
        $this->info(sprintf('Processing complete: %d dispatched, %d skipped', $dispatched, $skipped));

        return self::SUCCESS;
    }

    private function shouldSkipPrice(HostingPlanPrice $price, Carbon $today): bool
    {
        $conflictingPrice = HostingPlanPrice::query()
            ->where('hosting_plan_id', $price->hosting_plan_id)
            ->where('currency_id', $price->currency_id)
            ->where('billing_cycle', $price->billing_cycle)
            ->where('effective_date', '<=', $today->toDateString())
            ->where('is_current', false)
            ->where('id', '!=', $price->id)
            ->orderBy('effective_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->first();

        if ($conflictingPrice === null) {
            return false;
        }

        $priceEffectiveDate = $price->effective_date?->toDateString();
        $conflictingEffectiveDate = $conflictingPrice->effective_date?->toDateString();

        if ($conflictingEffectiveDate > $priceEffectiveDate) {
            return true;
        }

        if ($conflictingEffectiveDate === $priceEffectiveDate &&
            $conflictingPrice->created_at > $price->created_at) {
            return true;
        }

        return false;
    }
}
