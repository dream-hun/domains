<?php

declare(strict_types=1);

namespace App\Enums\Coupons;

enum CouponType: string
{
    case Percentage = 'percentage';
    case Fixed = 'fixed';

    public function label(): string
    {
        return match ($this) {
            self::Percentage => 'Percentage',
            self::Fixed => 'Fixed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Percentage => 'bg-blue-500',
            self::Fixed => 'bg-green-500',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Percentage => 'bi bi-percent',
            self::Fixed => 'bi bi-cash',
        };
    }
}
