<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\DomainPrice;

class DomainPriceObserver
{
    public function updating(DomainPrice $domainPrice): void
    {
        $priceFields = ['register_price', 'renewal_price', 'transfer_price', 'redemption_price'];

        if ($domainPrice->isDirty($priceFields)) {
            $changes = [];
            $oldValues = [];

            foreach ($priceFields as $field) {
                if ($domainPrice->isDirty($field)) {
                    $changes[$field] = $domainPrice->{$field};
                    $oldValues[$field] = $domainPrice->getOriginal($field);
                }
            }

            $domainPrice->domainPriceHistories()->create([
                'register_price' => $domainPrice->register_price,
                'renewal_price' => $domainPrice->renewal_price,
                'transfer_price' => $domainPrice->transfer_price,
                'redemption_price' => $domainPrice->redemption_price,
                'changes' => $changes,
                'old_values' => $oldValues,
                'changed_by' => auth()->id(),
                'reason' => request()->input('reason'),
                'ip_address' => request()->ip(),
            ]);
        }
    }
}
