<?php

declare(strict_types=1);

namespace App\Enums;

enum DomainStatus: string
{
    case Active = 'active';
    case Pending = 'pending';
    case Expired = 'expired';
    case Transferred = 'transferred';
    case Locked = 'locked';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Pending => 'Pending',
            self::Expired => 'Expired',
            self::Transferred => 'Transferred',
            self::Locked => 'Locked',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'bg-success',
            self::Pending => 'bg-warning',
            self::Expired => 'bg-danger',
            self::Transferred => 'bg-info',
            self::Locked => 'bg-secondary',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Active => 'bi bi-check-circle',
            self::Pending => 'bi bi-hourglass-half',
            self::Expired => 'bi bi-clock',
            self::Transferred => 'bi bi-arrow-right',
            self::Locked => 'bi bi-lock',
        };
    }
}
