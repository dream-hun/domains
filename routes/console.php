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

// Schedule pending jobs processing (every 15 minutes)
Schedule::command('app:process-pending-jobs --limit=50 --older-than=30')->everyFifteenMinutes();
