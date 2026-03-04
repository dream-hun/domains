<?php

declare(strict_types=1);

namespace App\Enums;

enum AllocationCategory: string
{
    case Insurance = 'insurance';
    case Savings = 'savings';
    case Pathway = 'pathway';
    case Administration = 'administration';

    public function label(): string
    {
        return match ($this) {
            self::Insurance => 'Insurance',
            self::Savings => 'Savings',
            self::Pathway => 'Pathway',
            self::Administration => 'Administration',
        };
    }
}
