<?php

declare(strict_types=1);

namespace App\Enums;

enum Permission: string
{
    // Courts
    case ViewCourts = 'view-courts';
    case CreateCourts = 'create-courts';
    case EditCourts = 'edit-courts';
    case DeleteCourts = 'delete-courts';

    // Games
    case ViewGames = 'view-games';
    case CreateGames = 'create-games';
    case EditGames = 'edit-games';
    case DeleteGames = 'delete-games';
    case ModerateGames = 'moderate-games';

    // Users
    case ViewUsers = 'view-users';
    case ManageUsers = 'manage-users';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $permission): string => $permission->value, self::cases());
    }

    public function label(): string
    {
        return match ($this) {
            self::ViewCourts => 'View Courts',
            self::CreateCourts => 'Create Courts',
            self::EditCourts => 'Edit Courts',
            self::DeleteCourts => 'Delete Courts',
            self::ViewGames => 'View Games',
            self::CreateGames => 'Create Games',
            self::EditGames => 'Edit Games',
            self::DeleteGames => 'Delete Games',
            self::ModerateGames => 'Moderate Games',
            self::ViewUsers => 'View Users',
            self::ManageUsers => 'Manage Users',
        };
    }
}
