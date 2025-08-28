<?php

declare(strict_types=1);

namespace App\Enums;

enum DomainType: string
{
    case Local = 'local';
    case International = 'international';

    public function color(): string
    {
        return match ($this) {
            self::Local => 'bg-success',
            self::International => 'bg-info',
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
