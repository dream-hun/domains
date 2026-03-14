<?php

declare(strict_types=1);

use App\Models\Domain;
use App\Models\User;
use App\Notifications\DomainExpiringNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

test('sends notification for domain expiring in 7 days', function (): void {
    Notification::fake();
    Date::setTestNow(Date::create(2026, 3, 14));

    $user = User::factory()->create();
    $domain = Domain::factory()->create([
        'owner_id' => $user->id,
        'status' => 'active',
        'auto_renew' => false,
        'expires_at' => Date::now()->addDays(7),
    ]);

    $this->artisan('domains:check-expiring --days=7')
        ->assertSuccessful();

    Notification::assertSentTo($user, DomainExpiringNotification::class, function ($notification) use ($domain): bool {
        return $notification->domain->id === $domain->id && $notification->daysUntilExpiry === 7;
    });
});

test('sends notification for domain expiring in 3 days', function (): void {
    Notification::fake();
    Date::setTestNow(Date::create(2026, 3, 14));

    $user = User::factory()->create();
    $domain = Domain::factory()->create([
        'owner_id' => $user->id,
        'status' => 'active',
        'auto_renew' => false,
        'expires_at' => Date::now()->addDays(3),
    ]);

    $this->artisan('domains:check-expiring --days=7')
        ->assertSuccessful();

    Notification::assertSentTo($user, DomainExpiringNotification::class, function ($notification): bool {
        return $notification->daysUntilExpiry === 3;
    });
});

test('sends notification for domain expiring in 1 day', function (): void {
    Notification::fake();
    Date::setTestNow(Date::create(2026, 3, 14));

    $user = User::factory()->create();
    Domain::factory()->create([
        'owner_id' => $user->id,
        'status' => 'active',
        'auto_renew' => false,
        'expires_at' => Date::now()->addDay(),
    ]);

    $this->artisan('domains:check-expiring --days=7')
        ->assertSuccessful();

    Notification::assertSentTo($user, DomainExpiringNotification::class, function ($notification): bool {
        return $notification->daysUntilExpiry === 1;
    });
});

test('sends notification for domain expiring today', function (): void {
    Notification::fake();
    Date::setTestNow(Date::create(2026, 3, 14));

    $user = User::factory()->create();
    Domain::factory()->create([
        'owner_id' => $user->id,
        'status' => 'active',
        'auto_renew' => false,
        'expires_at' => Date::now(),
    ]);

    $this->artisan('domains:check-expiring --days=7')
        ->assertSuccessful();

    Notification::assertSentTo($user, DomainExpiringNotification::class, function ($notification): bool {
        return $notification->daysUntilExpiry === 0;
    });
});

test('does not send notification for non-milestone days', function (): void {
    Notification::fake();
    Date::setTestNow(Date::create(2026, 3, 14));

    $user = User::factory()->create();
    Domain::factory()->create([
        'owner_id' => $user->id,
        'status' => 'active',
        'auto_renew' => false,
        'expires_at' => Date::now()->addDays(5),
    ]);

    $this->artisan('domains:check-expiring --days=7')
        ->assertSuccessful();

    Notification::assertNothingSent();
});

test('skips domains with auto renew enabled', function (): void {
    Notification::fake();
    Date::setTestNow(Date::create(2026, 3, 14));

    $user = User::factory()->create();
    Domain::factory()->create([
        'owner_id' => $user->id,
        'status' => 'active',
        'auto_renew' => true,
        'expires_at' => Date::now()->addDays(3),
    ]);

    $this->artisan('domains:check-expiring --days=7')
        ->assertSuccessful();

    Notification::assertNothingSent();
});

test('skips non-active domains', function (): void {
    Notification::fake();
    Date::setTestNow(Date::create(2026, 3, 14));

    $user = User::factory()->create();
    Domain::factory()->create([
        'owner_id' => $user->id,
        'status' => 'expired',
        'auto_renew' => false,
        'expires_at' => Date::now()->addDays(3),
    ]);

    $this->artisan('domains:check-expiring --days=7')
        ->assertSuccessful();

    Notification::assertNothingSent();
});

test('deduplicates notifications sent today', function (): void {
    Notification::fake();
    Date::setTestNow(Date::create(2026, 3, 14));

    $user = User::factory()->create();
    $domain = Domain::factory()->create([
        'owner_id' => $user->id,
        'status' => 'active',
        'auto_renew' => false,
        'expires_at' => Date::now()->addDays(3),
    ]);

    // Simulate a previously sent notification
    $user->notifications()->create([
        'id' => Illuminate\Support\Str::uuid()->toString(),
        'type' => DomainExpiringNotification::class,
        'data' => [
            'domain_id' => $domain->id,
            'days_until_expiry' => 3,
        ],
        'created_at' => Date::now(),
    ]);

    $this->artisan('domains:check-expiring --days=7')
        ->assertSuccessful();

    Notification::assertNothingSent();
});

test('outputs no expiring domains message when none found', function (): void {
    Notification::fake();

    $this->artisan('domains:check-expiring --days=7')
        ->expectsOutput('No expiring domains found.')
        ->assertSuccessful();
});
