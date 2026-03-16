<?php

declare(strict_types=1);

use App\Models\Order;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\RenewalInvoiceNotification;
use App\Services\SubscriptionInvoiceGenerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

test('generates renewal order for subscription expiring within window', function (): void {
    Date::setTestNow(Date::create(2026, 3, 14));
    Notification::fake();

    $user = User::factory()->create();
    Subscription::factory()->active()->autoRenew()->create([
        'user_id' => $user->id,
        'next_renewal_at' => Date::now()->addDays(5),
        'billing_cycle' => 'monthly',
    ]);

    $service = new SubscriptionInvoiceGenerationService;
    $results = $service->generateRenewalInvoices(7);

    expect($results['generated'])->toBe(1)
        ->and($results['failed'])->toBeEmpty();

    $order = Order::query()->where('user_id', $user->id)->first();
    expect($order)->not->toBeNull()
        ->and($order->type)->toBe('subscription_renewal')
        ->and($order->status)->toBe('pending');
});

test('sends renewal invoice notification after generating subscription order', function (): void {
    Date::setTestNow(Date::create(2026, 3, 14));
    Notification::fake();

    $user = User::factory()->create();
    Subscription::factory()->active()->autoRenew()->create([
        'user_id' => $user->id,
        'next_renewal_at' => Date::now()->addDays(5),
        'billing_cycle' => 'monthly',
    ]);

    $service = new SubscriptionInvoiceGenerationService;
    $service->generateRenewalInvoices(7);

    Notification::assertSentTo($user, RenewalInvoiceNotification::class);
});

test('skips subscriptions without auto renew', function (): void {
    Date::setTestNow(Date::create(2026, 3, 14));

    $user = User::factory()->create();
    Subscription::factory()->active()->create([
        'user_id' => $user->id,
        'auto_renew' => false,
        'next_renewal_at' => Date::now()->addDays(5),
        'billing_cycle' => 'monthly',
    ]);

    $service = new SubscriptionInvoiceGenerationService;
    $results = $service->generateRenewalInvoices(7);

    expect($results['generated'])->toBe(0);
});

test('skips non-active subscriptions', function (): void {
    Date::setTestNow(Date::create(2026, 3, 14));

    $user = User::factory()->create();
    Subscription::factory()->expired()->autoRenew()->create([
        'user_id' => $user->id,
        'next_renewal_at' => Date::now()->addDays(5),
        'billing_cycle' => 'monthly',
    ]);

    $service = new SubscriptionInvoiceGenerationService;
    $results = $service->generateRenewalInvoices(7);

    expect($results['generated'])->toBe(0);
});
