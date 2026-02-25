<?php

declare(strict_types=1);

namespace App\Enums;

enum CourtStatus: string
{
    case ACTIVE = 'active';
    case PILOT = 'pilot';
    case PRIORITY = 'priority';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::PILOT => 'Pilot',
            self::PRIORITY => 'Priority',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ACTIVE => 'bg-green-500',
            self::PILOT => 'bg-yellow-500',
            self::PRIORITY => 'bg-red-500',
        };
    }
}
