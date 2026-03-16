<?php

declare(strict_types=1);

use App\Jobs\GenerateSubscriptionRenewalInvoiceJob;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\RenewalInvoiceNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

test('creates order and sends notification for subscription renewal', function (): void {
    Date::setTestNow(Date::create(2026, 3, 14));
    Notification::fake();

    $user = User::factory()->create();
    $subscription = Subscription::factory()->active()->autoRenew()->create([
        'user_id' => $user->id,
        'next_renewal_at' => Date::now()->addDays(5),
        'billing_cycle' => 'monthly',
    ]);

    $job = new GenerateSubscriptionRenewalInvoiceJob($subscription);
    $job->handle(new App\Services\SubscriptionInvoiceGenerationService);

    $order = Order::query()->where('user_id', $user->id)->first();
    expect($order)->not->toBeNull()
        ->and($order->type)->toBe('subscription_renewal')
        ->and($order->status)->toBe('pending');

    $orderItem = OrderItem::query()->where('order_id', $order->id)->first();
    expect($orderItem)->not->toBeNull();

    Notification::assertSentTo($user, RenewalInvoiceNotification::class);
});

test('skips if shouldGenerateInvoice returns false', function (): void {
    Date::setTestNow(Date::create(2026, 3, 14));
    Notification::fake();

    $user = User::factory()->create();
    $subscription = Subscription::factory()->active()->autoRenew()->create([
        'user_id' => $user->id,
        'next_renewal_at' => Date::now()->addDays(5),
        'billing_cycle' => 'monthly',
        'last_invoice_generated_at' => Date::now(),
    ]);

    $job = new GenerateSubscriptionRenewalInvoiceJob($subscription);
    $job->handle(new App\Services\SubscriptionInvoiceGenerationService);

    expect(Order::query()->where('user_id', $user->id)->exists())->toBeFalse();

    Notification::assertNothingSent();
});
