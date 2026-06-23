<?php

declare(strict_types=1);

namespace App\Enums;

enum OrderStatus: string
{
    case Paid = 'paid';
    case Pending = 'pending';
    case Processing = 'processing';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';
    case Completed = 'completed';
    case Attention = 'requires_attention';
    case PartialCompleted = 'partially_completed';
    case Manual = 'manual';
}
