<?php

declare(strict_types=1);

namespace App\Enums;

enum ContactType: string
{
    case Registrant = 'registrant';
    case Billing = 'billing';
    case Technical = 'technical';
    case Administrative = 'admin';

    public function color(): string
    {
        return match ($this) {
            self::Registrant => 'bg-primary',
            self::Billing => 'bg-success',
            self::Technical => 'bg-info',
            self::Administrative => 'bg-secondary',
        };

    }

    public function label(): string
    {
        return match ($this) {
            self::Registrant => 'Registrant',
            self::Billing => 'Billing',
            self::Technical => 'Technical',
            self::Administrative => 'Administrative',
        };
    }
}
