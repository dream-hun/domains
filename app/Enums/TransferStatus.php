<?php

declare(strict_types=1);

namespace App\Enums;

enum TransferStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Accepted => 'Accepted',
            self::Rejected => 'Rejected',
            self::Expired => 'Expired',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'badge bg-warning',
            self::Accepted => 'badge bg-success',
            self::Rejected => 'badge bg-danger',
            self::Expired => 'badge bg-secondary',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Pending => 'bi bi-hourglass-half',
            self::Accepted => 'bi bi-check-circle',
            self::Rejected => 'bi bi-times-circle',
            self::Expired => 'bi bi-clock',
        };
    }
}
