<?php

declare(strict_types=1);

namespace App\Enums\Vps;

enum VpsInstanceStatus: string
{
    case Running = 'running';
    case Stopped = 'stopped';
    case Error = 'error';
    case Installing = 'installing';
    case Unknown = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::Running => 'Running',
            self::Stopped => 'Stopped',
            self::Error => 'Error',
            self::Installing => 'Installing',
            self::Unknown => 'Unknown',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Running => 'bg-success',
            self::Stopped => 'bg-secondary',
            self::Error => 'bg-danger',
            self::Installing => 'bg-warning',
            self::Unknown => 'bg-info',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Running => 'bi bi-play-circle',
            self::Stopped => 'bi bi-stop-circle',
            self::Error => 'bi bi-exclamation-triangle',
            self::Installing => 'bi bi-gear',
            self::Unknown => 'bi bi-question-circle',
        };
    }
}
