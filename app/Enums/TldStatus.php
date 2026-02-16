<?php

declare(strict_types=1);

namespace App\Enums;

enum TldStatus: string
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
            self::Active => 'bg-success',
            self::Inactive => 'bg-danger',
        };
    }
}
