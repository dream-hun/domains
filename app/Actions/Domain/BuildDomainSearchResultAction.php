<?php

declare(strict_types=1);

namespace App\Actions\Domain;

use App\Models\Tld;
use App\Services\PriceFormatter;

final class BuildDomainSearchResultAction
{
    /**
     * Build the domain result array used by Livewire domain search.
     *
     * @return array{available: bool, reason: string, register_price: float, transfer_price: float, renewal_price: float, formatted_price: string, display_currency_code: string, in_cart: bool, is_primary: bool, is_international: bool}
     */
    public function handle(
        Tld $tld,
        string $domainName,
        bool $available,
        string $reason,
        string $currencyCode,
        bool $inCart,
        bool $isPrimary,
        bool $isInternational
    ): array {
        $register = $tld->getDisplayPriceForCurrency($currencyCode, 'register_price');
        $transfer = $tld->getDisplayPriceForCurrency($currencyCode, 'transfer_price');
        $renewal = $tld->getDisplayPriceForCurrency($currencyCode, 'renewal_price');

        $formatter = resolve(PriceFormatter::class);

        return [
            'available' => $available,
            'reason' => $reason,
            'register_price' => $register['amount'],
            'transfer_price' => $transfer['amount'],
            'renewal_price' => $renewal['amount'],
            'formatted_price' => $formatter->format($register['amount'], $register['currency_code']),
            'display_currency_code' => $register['currency_code'],
            'in_cart' => $inCart,
            'is_primary' => $isPrimary,
            'is_international' => $isInternational,
        ];
    }
}
