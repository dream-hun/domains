<?php

declare(strict_types=1);

namespace App\Enums;

enum ContactProvider: string
{
    case NAMECHEAP = 'namecheap';
    case RICTA_EPP = 'epp';

    public function label(): string
    {
        return match ($this) {
            self::NAMECHEAP => 'NameCheap',
            self::RICTA_EPP => 'RICTA EPP',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::NAMECHEAP => 'bg-blue-500',
            self::RICTA_EPP => 'bg-green-500',
        };
    }
}
