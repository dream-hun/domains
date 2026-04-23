<?php

declare(strict_types=1);

use App\Models\Domain;
use App\Models\User;
use App\Notifications\DomainAutoRenewalFailedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;

uses(RefreshDatabase::class);

test('notification uses mail and database channels', function (): void {
    $domain = Domain::factory()->create([
        'expires_at' => Date::now()->addDays(3),
    ]);

    $notification = new DomainAutoRenewalFailedNotification($domain, 'Payment failed');
    $channels = $notification->via(new stdClass);

    expect($channels)->toBe(['mail', 'database']);
});

test('mail subject indicates renewal failure', function (): void {
    $user = User::factory()->create();
    $domain = Domain::factory()->create([
        'expires_at' => Date::now()->addDays(3),
    ]);

    $notification = new DomainAutoRenewalFailedNotification($domain, 'Payment failed');
    $mail = $notification->toMail($user);

    expect($mail->subject)->toBe('Automatic Domain Renewal Failed');
});

test('toArray returns correct structure', function (): void {
    $domain = Domain::factory()->create([
        'expires_at' => Date::now()->addDays(3),
    ]);

    $notification = new DomainAutoRenewalFailedNotification($domain, 'Payment declined');
    $array = $notification->toArray(new stdClass);

    expect($array)
        ->toHaveKey('type', 'domain_auto_renewal_failed')
        ->toHaveKey('domain_id', $domain->id)
        ->toHaveKey('domain_name', $domain->name)
        ->toHaveKey('failure_reason', 'Payment declined')
        ->toHaveKey('expires_at');
});
