<?php

declare(strict_types=1);

namespace App\Traits;

use App\Helpers\CurrencyHelper;
use Exception;

trait CalculatesCartDiscount
{
    protected function calculateSessionDiscount(float $subtotal, string $currency): float
    {
        if (! session()->has('coupon')) {
            return 0;
        }

        $couponData = session('coupon');
        $couponCurrency = $couponData['currency'] ?? 'USD';
        $discountAmount = (float) ($couponData['discount_amount'] ?? 0);

        if ($couponCurrency !== $currency) {
            try {
                $discountAmount = CurrencyHelper::convert($discountAmount);
            } catch (Exception) {
                $type = $couponData['type'] ?? 'percentage';
                $value = (float) ($couponData['value'] ?? 0);

                if ($type === 'percentage') {
                    $discountAmount = $subtotal * ($value / 100);
                } else {
                    try {
                        $discountAmount = CurrencyHelper::convert($value);
                    } catch (Exception) {
                        $discountAmount = $value;
                    }
                }
            }
        }

        return min($discountAmount, $subtotal);
    }

    protected function getBillingCycleMonths(string $billingCycle): int
    {
        return match ($billingCycle) {
            'quarterly' => 3,
            'semi-annually' => 6,
            'annually' => 12,
            'biennially' => 24,
            'triennially' => 36,
            default => 1,
        };
    }
}
