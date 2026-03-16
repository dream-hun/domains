<?php

declare(strict_types=1);

use App\Models\Domain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

test('command generates invoices for domains expiring within window', function (): void {
    Date::setTestNow(Date::create(2026, 3, 14));
    Notification::fake();

    $user = User::factory()->create();
    Domain::factory()->create([
        'owner_id' => $user->id,
        'status' => 'active',
        'auto_renew' => true,
        'expires_at' => Date::now()->addDays(5),
        'years' => 1,
    ]);

    $this->artisan('domains:generate-renewal-invoices --days=7')
        ->expectsOutput('Generated 1 renewal invoices.')
        ->assertSuccessful();
});

test('command reports zero when no domains found', function (): void {
    Date::setTestNow(Date::create(2026, 3, 14));

    $this->artisan('domains:generate-renewal-invoices --days=7')
        ->expectsOutput('Generated 0 renewal invoices.')
        ->assertSuccessful();
});

test('command accepts custom days option', function (): void {
    Date::setTestNow(Date::create(2026, 3, 14));
    Notification::fake();

    $user = User::factory()->create();
    Domain::factory()->create([
        'owner_id' => $user->id,
        'status' => 'active',
        'auto_renew' => true,
        'expires_at' => Date::now()->addDays(12),
        'years' => 1,
    ]);

    $this->artisan('domains:generate-renewal-invoices --days=14')
        ->expectsOutput('Generated 1 renewal invoices.')
        ->assertSuccessful();
});

test('command generates invoices for domains expiring within 90-day window', function (): void {
    Date::setTestNow(Date::create(2026, 3, 14));
    Notification::fake();

    $user = User::factory()->create();
    Domain::factory()->create([
        'owner_id' => $user->id,
        'status' => 'active',
        'auto_renew' => true,
        'expires_at' => Date::now()->addDays(60),
        'years' => 1,
    ]);

    $this->artisan('domains:generate-renewal-invoices --days=90')
        ->expectsOutput('Generated 1 renewal invoices.')
        ->assertSuccessful();
});
