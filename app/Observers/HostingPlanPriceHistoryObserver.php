<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\HostingPlanPrice;

class HostingPlanPriceHistoryObserver
{
    /**
     * Handle the HostingPlanPrice "creating" event.
     * When is_current = true, deactivate other current records for the same hosting_plan_id + currency_id + billing_cycle.
     */
    public function creating(HostingPlanPrice $planPrice): void
    {
        if ($planPrice->is_current) {
            $this->deactivateOtherCurrentPricings($planPrice);
        }
    }

    /**
     * Handle the HostingPlanPrice "updating" event.
     * Track price changes and manage is_current flag.
     */
    public function updating(HostingPlanPrice $planPrice): void
    {
        if ($planPrice->isDirty('is_current') && $planPrice->is_current) {
            $this->deactivateOtherCurrentPricings($planPrice);
        }

        $priceFields = ['regular_price', 'renewal_price'];

        if ($planPrice->isDirty($priceFields)) {
            $changes = [];
            $oldValues = [];

            foreach ($priceFields as $field) {
                if ($planPrice->isDirty($field)) {
                    $changes[$field] = $planPrice->{$field};
                    $oldValues[$field] = $planPrice->getOriginal($field);
                }
            }

            $planPrice->hostingPlanPriceHistories()->create([
                'regular_price' => $planPrice->regular_price,
                'renewal_price' => $planPrice->renewal_price,
                'changes' => $changes,
                'old_values' => $oldValues,
                'changed_by' => auth()->id(),
                'reason' => request()->input('reason'),
                'ip_address' => request()->ip(),
            ]);
        }
    }

    private function deactivateOtherCurrentPricings(HostingPlanPrice $planPrice): void
    {
        HostingPlanPrice::query()
            ->where('hosting_plan_id', $planPrice->hosting_plan_id)
            ->where('currency_id', $planPrice->currency_id)
            ->where('billing_cycle', $planPrice->billing_cycle)
            ->where('is_current', true)
            ->when($planPrice->exists, fn ($query) => $query->where('id', '!=', $planPrice->id))
            ->update(['is_current' => false]);
    }
}
