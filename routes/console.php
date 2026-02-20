<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function (): void {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule subscription expiration checks
Schedule::command('subscriptions:check-expiring --days=7')->daily()->at('09:00');

// Schedule subscription renewal invoice generation
Schedule::command('subscriptions:generate-renewal-invoices --days=7')->daily()->at('08:00');

// Schedule pending jobs processing (every 15 minutes)
Schedule::command('app:process-pending-jobs --limit=50 --older-than=30')->everyFifteenMinutes();

// Schedule hosting plan price activation (daily at 00:05)
Schedule::command('hosting-prices:activate-effective')->dailyAt('00:05');
