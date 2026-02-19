<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\DomainPriceCurrency;

final class DomainPriceCurrencyObserver
{
    private const PRICE_FIELDS = [
        'registration_price',
        'renewal_price',
        'transfer_price',
        'redemption_price',
    ];

    public function updating(DomainPriceCurrency $domainPriceCurrency): void
    {
        $changedFields = [];
        foreach (self::PRICE_FIELDS as $field) {
            if ($domainPriceCurrency->isDirty($field)) {
                $changedFields[$field] = $domainPriceCurrency->{$field};
            }
        }

        if ($changedFields === []) {
            return;
        }

        $oldValues = [];
        foreach (array_keys($changedFields) as $field) {
            $oldValues[$field] = $domainPriceCurrency->getOriginal($field);
        }

        $currencyCode = $domainPriceCurrency->currency?->code ?? 'USD';
        $changesForHistory = $this->formatChangesForHistory($changedFields, $currencyCode);
        $oldValuesForHistory = $this->formatChangesForHistory($oldValues, $currencyCode);

        $domainPriceCurrency->domainPrice->domainPriceHistories()->create([
            'domain_price_currency_id' => $domainPriceCurrency->id,
            'register_price' => (int) round((float) $domainPriceCurrency->registration_price),
            'renewal_price' => (int) round((float) $domainPriceCurrency->renewal_price),
            'transfer_price' => (int) round((float) $domainPriceCurrency->transfer_price),
            'redemption_price' => $domainPriceCurrency->redemption_price !== null
                ? (int) round((float) $domainPriceCurrency->redemption_price)
                : null,
            'changes' => $changesForHistory,
            'old_values' => $oldValuesForHistory,
            'changed_by' => auth()->id(),
            'reason' => request()->input('reason'),
            'ip_address' => request()->ip(),
        ]);
    }

    /**
     * Format values for history (prices stored in major units).
     *
     * @param  array<string, mixed>  $values
     * @return array<string, int|float>
     */
    private function formatChangesForHistory(array $values, string $currencyCode): array
    {
        $map = [
            'registration_price' => 'register_price',
            'renewal_price' => 'renewal_price',
            'transfer_price' => 'transfer_price',
            'redemption_price' => 'redemption_price',
        ];
        $result = [];
        foreach ($map as $from => $to) {
            if (! array_key_exists($from, $values)) {
                continue;
            }

            $val = $values[$from];
            $result[$to] = (int) round((float) $val);
        }

        return $result;
    }
}
