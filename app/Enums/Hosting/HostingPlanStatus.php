<?php

declare(strict_types=1);

namespace App\Enums\Hosting;

enum HostingPlanStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Draft = 'draft';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Inactive => 'Inactive',
            self::Draft => 'Draft',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'bg-success',
            self::Inactive => 'bg-danger',
            self::Draft => 'bg-warning',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Active => 'bi bi-check-circle',
            self::Inactive => 'bi bi-x-circle',
            self::Draft => 'bi bi-pencil-square',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Active => 'badge bg-success',
            self::Inactive => 'badge bg-danger',
            self::Draft => 'badge bg-warning',
        };
    }
}
