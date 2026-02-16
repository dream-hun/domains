<?php

declare(strict_types=1);

namespace App\Actions\TldPricing;

use App\Models\TldPricing;

final class UpdateTldPricingAction
{
    /**
     * @param  array{tld_id?: int|string|null, currency_id: int|string, register_price: int|string, renew_price: int|string, redemption_price?: int|string|null, transfer_price?: int|string|null, is_current: bool, effective_date: string}  $validated
     */
    public function handle(TldPricing $tldPricing, array $validated): TldPricing
    {
        $tldPricing->update([
            'tld_id' => $validated['tld_id'] ?? null,
            'currency_id' => $validated['currency_id'],
            'register_price' => $validated['register_price'],
            'renew_price' => $validated['renew_price'],
            'redemption_price' => $validated['redemption_price'] ?? null,
            'transfer_price' => $validated['transfer_price'] ?? null,
            'is_current' => $validated['is_current'] ?? true,
            'effective_date' => $validated['effective_date'],
        ]);

        return $tldPricing;
    }
}
