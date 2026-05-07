<?php

declare(strict_types=1);

namespace App\Enums;

enum ContactProvider: string
{
    case Namecheap = 'namecheap';

    case RictaEpp = 'epp';

    public function label(): string
    {
        return match ($this) {
            self::Namecheap => 'NameCheap',
            self::RictaEpp => 'RICTA EPP',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Namecheap => 'bg-info',
            self::RictaEpp => 'bg-warning',
        };
    }
}
