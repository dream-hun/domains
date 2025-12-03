<?php

declare(strict_types=1);

namespace App\Enums\Hosting;

enum BillingCycle: string
{
    case Monthly = 'monthly';

    case Quarterly = 'quarterly';

    case SemiAnnually = 'semi-annually';

    case Annually = 'annually';

    case Biennially = 'biennially';

    case Triennially = 'triennially';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(static fn (self $cycle): string => $cycle->value, self::cases());
    }

    public function label(): string
    {
        return match ($this) {
            self::Monthly => 'Monthly',
            self::Quarterly => 'Quarterly',
            self::SemiAnnually => 'Semi-Annually',
            self::Annually => 'Annually',
            self::Biennially => 'Biennially',
            self::Triennially => 'Triennially',
        };
    }
}
