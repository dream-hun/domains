<?php

declare(strict_types=1);

namespace App\Actions\Hosting\PlanPrices;

use App\Models\HostingPlanPrice;
use Illuminate\Support\Facades\DB;

final class ActivateHostingPlanPriceAction
{
    public function handle(HostingPlanPrice $planPrice): void
    {
        if ($planPrice->is_current) {
            return;
        }

        DB::transaction(function () use ($planPrice): void {
            $previousCurrentPrice = $this->findPreviousCurrentPrice($planPrice);

            if ($previousCurrentPrice !== null) {
                $previousCurrentPrice->update(['is_current' => false]);
            }

            $planPrice->update(['is_current' => true]);

            $this->createHistoryEntry($planPrice, $previousCurrentPrice);
        });
    }

    private function findPreviousCurrentPrice(HostingPlanPrice $planPrice): ?HostingPlanPrice
    {
        return HostingPlanPrice::query()
            ->where('hosting_plan_id', $planPrice->hosting_plan_id)
            ->where('currency_id', $planPrice->currency_id)
            ->where('billing_cycle', $planPrice->billing_cycle)
            ->where('is_current', true)
            ->where('id', '!=', $planPrice->id)
            ->first();
    }

    private function createHistoryEntry(HostingPlanPrice $planPrice, ?HostingPlanPrice $previousPrice): void
    {
        $planPrice->hostingPlanPriceHistories()->create([
            'regular_price' => $planPrice->regular_price,
            'renewal_price' => $planPrice->renewal_price,
            'changes' => [
                'regular_price' => $planPrice->regular_price,
                'renewal_price' => $planPrice->renewal_price,
            ],
            'old_values' => $previousPrice ? [
                'regular_price' => $previousPrice->regular_price,
                'renewal_price' => $previousPrice->renewal_price,
            ] : [],
            'changed_by' => null,
            'reason' => 'Automatically activated on effective date',
            'ip_address' => request()->ip() ?? 'system',
        ]);
    }
}
