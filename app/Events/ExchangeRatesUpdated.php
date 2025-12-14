<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final readonly class ExchangeRatesUpdated
{
    use Dispatchable;

    use SerializesModels;

    public function __construct(
        public int $updatedCount,
        public array $updatedCurrencies
    ) {}
}
