<?php

declare(strict_types=1);

namespace App\Enums\Hosting;

enum CategoryStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Inactive => 'Inactive',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'bg-primary',
            self::Inactive => 'bg-success',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Active => 'bi bi-check-circle',
            self::Inactive => 'bi bi-x-circle',
            self::Pending => 'bi bi-clock',
            self::Suspended => 'bi bi-pause-circle',
            self::Cancelled => 'bi bi-x-octagon',
        };
    }
}
