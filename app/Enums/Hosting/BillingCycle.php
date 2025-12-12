<?php

declare(strict_types=1);

namespace App\Enums\Hosting;

enum BillingCycle: string
{
    case Monthly = 'monthly';

    case Annually = 'annually';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(static fn (self $cycle): string => $cycle->value, self::cases());
    }

    public function label(): string
    {
        return match ($this) {
            self::Monthly => 'Monthly',
            self::Annually => 'Annually',
        };
    }

    /**
     * Get the number of months for this billing cycle
     */
    public function toMonths(): int
    {
        return match ($this) {
            self::Monthly => 1,
            self::Annually => 12,
        };
    }
}
