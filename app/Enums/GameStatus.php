<?php

declare(strict_types=1);

namespace App\Enums;

enum GameStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Flagged = 'flagged';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Flagged => 'Flagged',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'bg-yellow-500',
            self::Approved => 'bg-green-500',
            self::Rejected => 'bg-red-500',
            self::Flagged => 'bg-orange-500',
        };
    }
}
