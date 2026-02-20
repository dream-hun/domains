<?php

declare(strict_types=1);

namespace App\Actions\Hosting\PlanPrices;

use App\Jobs\ActivateHostingPlanPriceJob;
use App\Models\HostingPlanPrice;
use Carbon\Carbon;
use Illuminate\Support\Facades\Date;

final class UpdatePlanPriceAction
{
    public function __construct(
        private readonly ActivateHostingPlanPriceAction $activateAction
    ) {}

    public function handle(string $uuid, array $data): void
    {
        $planPrice = HostingPlanPrice::query()
            ->with('currency')
            ->where('uuid', $uuid)
            ->firstOrFail();

        unset($data['reason']);

        $newEffectiveDate = isset($data['effective_date'])
            ? Carbon::parse($data['effective_date'])
            : $planPrice->effective_date;

        $today = Date::today();

        if ($newEffectiveDate->isFuture()) {
            $this->handleFutureEffectiveDate($planPrice, $data, $newEffectiveDate, $today);

            return;
        }

        if ($newEffectiveDate->lte($today)) {
            $this->handlePastOrCurrentEffectiveDate($planPrice, $data);

            return;
        }

        $planPrice->update($data);
    }

    private function handleFutureEffectiveDate(
        HostingPlanPrice $planPrice,
        array $data,
        Carbon $effectiveDate,
        Carbon $today
    ): void {
        $data['is_current'] = false;
        $planPrice->update($data);

        $delaySeconds = $today->diffInSeconds($effectiveDate, false);
        if ($delaySeconds > 0) {
            ActivateHostingPlanPriceJob::dispatch($planPrice->uuid)
                ->delay(now()->addSeconds($delaySeconds));
        }
    }

    private function handlePastOrCurrentEffectiveDate(HostingPlanPrice $planPrice, array $data): void
    {
        $planPrice->update($data);
        $planPrice->refresh();

        $shouldActivate = ($data['is_current'] ?? true) !== false && ! $planPrice->is_current;

        if ($shouldActivate) {
            $this->activateAction->handle($planPrice);
        }
    }
}
