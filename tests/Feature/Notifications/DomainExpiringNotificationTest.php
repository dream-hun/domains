<?php

declare(strict_types=1);

use App\Models\Domain;
use App\Models\User;
use App\Notifications\DomainExpiringNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;

uses(RefreshDatabase::class);

test('notification uses mail and database channels', function (): void {
    $domain = Domain::factory()->create([
        'expires_at' => Date::now()->addDays(7),
    ]);

    $notification = new DomainExpiringNotification($domain, 7);
    $channels = $notification->via(new stdClass);

    expect($channels)->toBe(['mail', 'database']);
});

test('mail subject varies by days until expiry', function (): void {
    $user = User::factory()->create();
    $domain = Domain::factory()->create([
        'expires_at' => Date::now()->addDays(7),
    ]);

    $notification = new DomainExpiringNotification($domain, 7);
    $mail = $notification->toMail($user);

    expect($mail->subject)->toBe('Your Domain Expires in 7 Days');
});

test('mail subject for 1 day uses singular', function (): void {
    $user = User::factory()->create();
    $domain = Domain::factory()->create([
        'expires_at' => Date::now()->addDay(),
    ]);

    $notification = new DomainExpiringNotification($domain, 1);
    $mail = $notification->toMail($user);

    expect($mail->subject)->toBe('Your Domain Expires in 1 Day');
});

test('mail subject for 0 days says expires today', function (): void {
    $user = User::factory()->create();
    $domain = Domain::factory()->create([
        'expires_at' => Date::now(),
    ]);

    $notification = new DomainExpiringNotification($domain, 0);
    $mail = $notification->toMail($user);

    expect($mail->subject)->toBe('Your Domain Expires Today');
});

test('toArray returns correct structure', function (): void {
    $domain = Domain::factory()->create([
        'expires_at' => Date::now()->addDays(3),
    ]);

    $notification = new DomainExpiringNotification($domain, 3);
    $array = $notification->toArray(new stdClass);

    expect($array)
        ->toHaveKey('type', 'domain_expiring')
        ->toHaveKey('domain_id', $domain->id)
        ->toHaveKey('domain_name', $domain->name)
        ->toHaveKey('days_until_expiry', 3)
        ->toHaveKey('expires_at');
});
