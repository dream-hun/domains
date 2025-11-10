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
        $coupon = Coupon::query()->where('code', $code)->first();
        throw_unless($coupon, Exception::class, 'Invalid coupon code');

        throw_if($coupon->valid_from && now()->lt($coupon->valid_from), Exception::class, 'Coupon is not yet active.');
        throw_if($coupon->valid_to && now()->gt($coupon->valid_to), Exception::class, 'Coupon has expired.');
        throw_if($coupon->max_uses && $coupon->uses >= $coupon->max_uses, Exception::class, 'Coupon usage limit reached.');

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
