<?php

declare(strict_types=1);

namespace App\Actions\TldPricing;

use App\Models\TldPricing;
use Illuminate\Support\Facades\DB;

final class ActivateTldPricingAction
{
    public function handle(TldPricing $tldPricing): void
    {
        if ($tldPricing->is_current) {
            return;
        }

        DB::transaction(function () use ($tldPricing): void {
            $previousCurrentPricing = $this->findPreviousCurrentPricing($tldPricing);

            if ($previousCurrentPricing !== null) {
                $previousCurrentPricing->update(['is_current' => false]);
            }

            $tldPricing->update(['is_current' => true]);

            $this->createHistoryEntry($tldPricing, $previousCurrentPricing);
        });
    }

    private function findPreviousCurrentPricing(TldPricing $tldPricing): ?TldPricing
    {
        return TldPricing::query()
            ->where('tld_id', $tldPricing->tld_id)
            ->where('currency_id', $tldPricing->currency_id)
            ->where('is_current', true)
            ->where('id', '!=', $tldPricing->id)
            ->first();
    }

    private function createHistoryEntry(TldPricing $tldPricing, ?TldPricing $previousPricing): void
    {
        $currentPrices = $this->extractPrices($tldPricing);
        $oldPrices = $previousPricing ? $this->extractPrices($previousPricing) : [];

        $tldPricing->domainPriceHistories()->create([
            'register_price' => $tldPricing->register_price,
            'renewal_price' => $tldPricing->renew_price,
            'transfer_price' => $tldPricing->transfer_price ?? 0,
            'redemption_price' => $tldPricing->redemption_price,
            'changes' => $currentPrices,
            'old_values' => $oldPrices,
            'changed_by' => null,
            'reason' => 'Automatically activated on effective date',
            'ip_address' => request()->ip() ?? 'system',
        ]);
    }

    /**
     * @return array{register_price: int, renew_price: int, transfer_price: int|null, redemption_price: int|null}
     */
    private function extractPrices(TldPricing $pricing): array
    {
        return [
            'register_price' => $pricing->register_price,
            'renew_price' => $pricing->renew_price,
            'transfer_price' => $pricing->transfer_price,
            'redemption_price' => $pricing->redemption_price,
        ];
    }
}
