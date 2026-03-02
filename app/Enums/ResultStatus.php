<?php

declare(strict_types=1);

namespace App\Enums;

enum ResultStatus: string
{
    case WIN = 'win';
    case LOST = 'lost';

    public function label(): string
    {
        return match ($this) {
            self::WIN => 'Win',
            self::LOST => 'Lost',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::WIN => 'green',
            self::LOST => 'red',
        };
    }
}
