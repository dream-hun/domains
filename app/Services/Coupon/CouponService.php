<?php

declare(strict_types=1);

namespace App\Services\Coupon;

use App\Models\Coupon;
use Exception;

final class CouponService
{
    /**
     * @throws Exception
     */
    public function validateCoupon($code): Coupon
    {
        $coupon = Coupon::where('code', $code)->first();
        if (! $coupon) {
            throw new Exception('Invalid coupon code');
        }
        if ($coupon->valid_from && now()->lt($coupon->valid_from)) {
            throw new Exception('Coupon is not yet active.');
        }

        if ($coupon->valid_to && now()->gt($coupon->valid_to)) {
            throw new Exception('Coupon has expired.');
        }

        if ($coupon->max_uses && $coupon->uses >= $coupon->max_uses) {
            throw new Exception('Coupon usage limit reached.');
        }

        return $coupon;

    }

    public function applyCoupon($coupon, $amount)
    {
        $type = is_string($coupon->type) ? $coupon->type : $coupon->type->value;

        if ($type === 'fixed') {
            return max(0, $amount - $coupon->value);
        }
        if ($type === 'percentage') {
            return $amount - ($amount * ($coupon->value / 100));
        }

        return $amount;
    }
}
