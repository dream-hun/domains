<?php

declare(strict_types=1);

namespace App\Enums;

enum OrderStatus: string
{
    case PAID = 'paid';
    case PENDING = 'pending';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
    case REFUNDED = 'refunded';
    case COMPLETED = 'completed';
    case ATTENTION = 'requires_attention';
    case PARTIAL_COMPLETED = 'partial_completed';

}
