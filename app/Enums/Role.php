<?php

declare(strict_types=1);

namespace App\Enums;

enum Role: string
{
    case Administrator = 'administrator';
    case Moderator = 'moderator';
    case Player = 'player';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $role): string => $role->value, self::cases());
    }

    public function label(): string
    {
        return match ($this) {
            self::Administrator => 'Administrator',
            self::Moderator => 'Moderator',
            self::Player => 'Player',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Administrator => 'bg-purple-500',
            self::Moderator => 'bg-blue-500',
            self::Player => 'bg-green-500',
        };
    }
}
