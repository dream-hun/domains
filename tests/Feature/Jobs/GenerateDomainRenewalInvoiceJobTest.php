<?php

declare(strict_types=1);

use App\Jobs\GenerateDomainRenewalInvoiceJob;
use App\Models\Domain;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Notifications\RenewalInvoiceNotification;
use App\Services\DomainInvoiceGenerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

test('creates order and sends notification for domain renewal', function (): void {
    Date::setTestNow(Date::create(2026, 3, 14));
    Notification::fake();

    $user = User::factory()->create();
    $domain = Domain::factory()->create([
        'owner_id' => $user->id,
        'status' => 'active',
        'auto_renew' => true,
        'expires_at' => Date::now()->addDays(5),
        'years' => 1,
    ]);

    $job = new GenerateDomainRenewalInvoiceJob($domain);
    $job->handle(new DomainInvoiceGenerationService);

    $order = Order::query()->where('user_id', $user->id)->first();
    expect($order)->not->toBeNull()
        ->and($order->type)->toBe('renewal')
        ->and($order->status)->toBe('pending');

    $orderItem = OrderItem::query()->where('order_id', $order->id)->first();
    expect($orderItem)->not->toBeNull()
        ->and($orderItem->domain_name)->toBe($domain->name)
        ->and($orderItem->domain_type)->toBe('renewal');

    Notification::assertSentTo($user, RenewalInvoiceNotification::class);
});

test('uses custom pricing when domain has custom price', function (): void {
    Date::setTestNow(Date::create(2026, 3, 14));
    Notification::fake();

    $user = User::factory()->create();
    $domain = Domain::factory()->create([
        'owner_id' => $user->id,
        'status' => 'active',
        'auto_renew' => true,
        'expires_at' => Date::now()->addDays(5),
        'is_custom_price' => true,
        'custom_price' => 25.00,
        'custom_price_currency' => 'EUR',
        'years' => 1,
    ]);

    $job = new GenerateDomainRenewalInvoiceJob($domain);
    $job->handle(new DomainInvoiceGenerationService);

    $order = Order::query()->where('user_id', $user->id)->first();
    expect($order->total_amount)->toBe('25.00')
        ->and($order->currency)->toBe('EUR');
});

test('calculates correct total for multi-year domain renewal', function (): void {
    Date::setTestNow(Date::create(2026, 3, 14));
    Notification::fake();

    $user = User::factory()->create();
    $domain = Domain::factory()->create([
        'owner_id' => $user->id,
        'status' => 'active',
        'auto_renew' => true,
        'expires_at' => Date::now()->addDays(5),
        'is_custom_price' => true,
        'custom_price' => 10.00,
        'custom_price_currency' => 'USD',
        'years' => 3,
    ]);

    $job = new GenerateDomainRenewalInvoiceJob($domain);
    $job->handle(new DomainInvoiceGenerationService);

    $order = Order::query()->where('user_id', $user->id)->first();
    expect($order->total_amount)->toBe('30.00');

    $orderItem = OrderItem::query()->where('order_id', $order->id)->first();
    expect($orderItem->total_amount)->toBe('30.00')
        ->and($orderItem->quantity)->toBe(3);
});

test('skips if pending renewal order already exists', function (): void {
    Date::setTestNow(Date::create(2026, 3, 14));
    Notification::fake();

    $user = User::factory()->create();
    $domain = Domain::factory()->create([
        'owner_id' => $user->id,
        'status' => 'active',
        'auto_renew' => true,
        'expires_at' => Date::now()->addDays(5),
        'years' => 1,
    ]);

    $existingOrder = Order::factory()->pending()->create([
        'user_id' => $user->id,
    ]);
    OrderItem::factory()->create([
        'order_id' => $existingOrder->id,
        'domain_name' => $domain->name,
        'domain_type' => 'renewal',
    ]);

    $job = new GenerateDomainRenewalInvoiceJob($domain);
    $job->handle(new DomainInvoiceGenerationService);

    // Should still only have the one pre-existing order
    expect(Order::query()->where('user_id', $user->id)->count())->toBe(1);

    Notification::assertNothingSent();
});
