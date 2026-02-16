<?php

declare(strict_types=1);

namespace App\Enums;

enum TldType: string
{
    case Local = 'local';

    case International = 'international';

    public function color(): string
    {
        return match ($this) {
            self::Local => 'badge-success',
            self::International => 'badge-info',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Local => 'Local',
            self::International => 'International',
        };

    }
}
