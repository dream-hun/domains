<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\TldPricing;

class TldPricingObserver
{
    /**
     * Handle the TldPricing "creating" event.
     * When is_current = true, deactivate other current records for the same tld_id + currency_id.
     */
    public function creating(TldPricing $tldPricing): void
    {
        if ($tldPricing->is_current) {
            $this->deactivateOtherCurrentPricings($tldPricing);
        }
    }

    /**
     * Handle the TldPricing "updating" event.
     * Track price changes and manage is_current flag.
     */
    public function updating(TldPricing $tldPricing): void
    {
        if ($tldPricing->isDirty('is_current') && $tldPricing->is_current) {
            $this->deactivateOtherCurrentPricings($tldPricing);
        }

        $priceFields = ['register_price', 'renew_price', 'redemption_price', 'transfer_price'];

        if ($tldPricing->isDirty($priceFields)) {
            $changes = [];
            $oldValues = [];

            foreach ($priceFields as $field) {
                if ($tldPricing->isDirty($field)) {
                    $changes[$field] = $tldPricing->{$field};
                    $oldValues[$field] = $tldPricing->getOriginal($field);
                }
            }

            $tldPricing->domainPriceHistories()->create([
                'register_price' => $tldPricing->register_price,
                'renewal_price' => $tldPricing->renew_price,
                'transfer_price' => $tldPricing->transfer_price ?? 0,
                'redemption_price' => $tldPricing->redemption_price,
                'changes' => $changes,
                'old_values' => $oldValues,
                'changed_by' => auth()->id(),
                'reason' => request()->input('reason'),
                'ip_address' => request()->ip(),
            ]);
        }
    }

    private function deactivateOtherCurrentPricings(TldPricing $tldPricing): void
    {
        TldPricing::query()
            ->where('tld_id', $tldPricing->tld_id)
            ->where('currency_id', $tldPricing->currency_id)
            ->where('is_current', true)
            ->when($tldPricing->exists, fn ($query) => $query->where('id', '!=', $tldPricing->id))
            ->update(['is_current' => false]);
    }
}
