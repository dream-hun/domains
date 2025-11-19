<?php

declare(strict_types=1);

namespace App\Enums;

enum DomainStatus: string
{
    case Active = 'active';
    case Pending = 'pending';
    case Expired = 'expired';
    case TransferPending = 'transfer_pending';
    case TransferInProgress = 'transfer_in_progress';
    case Transferred = 'transferred';
    case Locked = 'locked';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Pending => 'Pending',
            self::Expired => 'Expired',
            self::TransferPending => 'Transfer Pending',
            self::TransferInProgress => 'Transfer In Progress',
            self::Transferred => 'Transferred',
            self::Locked => 'Locked',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'bg-success',
            self::Pending => 'bg-warning',
            self::Expired => 'bg-danger',
            self::TransferPending => 'bg-warning',
            self::TransferInProgress => 'bg-primary',
            self::Transferred => 'bg-info',
            self::Locked => 'bg-secondary',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Active => 'bi bi-check-circle',
            self::Pending => 'bi bi-hourglass-half',
            self::Expired => 'bi bi-clock',
            self::TransferPending => 'bi bi-arrow-repeat',
            self::TransferInProgress => 'bi bi-arrow-clockwise',
            self::Transferred => 'bi bi-arrow-right',
            self::Locked => 'bi bi-lock',
        };
    }
}
