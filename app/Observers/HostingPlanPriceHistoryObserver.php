<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\HostingPlanPrice;

class HostingPlanPriceHistoryObserver
{
    public function updating(HostingPlanPrice $planPrice): void
    {
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
}
