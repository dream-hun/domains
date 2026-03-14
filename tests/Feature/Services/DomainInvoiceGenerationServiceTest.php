<?php

declare(strict_types=1);

use App\Models\Domain;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\DomainInvoiceGenerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;

uses(RefreshDatabase::class);

test('generates renewal order for domain expiring within window', function (): void {
    Date::setTestNow(Date::create(2026, 3, 14));

    $user = User::factory()->create();
    $domain = Domain::factory()->create([
        'owner_id' => $user->id,
        'status' => 'active',
        'auto_renew' => true,
        'expires_at' => Date::now()->addDays(5),
        'years' => 1,
    ]);

    $service = new DomainInvoiceGenerationService;
    $results = $service->generateRenewalInvoices(7);

    expect($results['generated'])->toBe(1)
        ->and($results['failed'])->toBeEmpty();

    $order = Order::query()->where('user_id', $user->id)->first();
    expect($order)->not->toBeNull()
        ->and($order->type)->toBe('renewal')
        ->and($order->status)->toBe('pending');

    $orderItem = OrderItem::query()->where('order_id', $order->id)->first();
    expect($orderItem)->not->toBeNull()
        ->and($orderItem->domain_name)->toBe($domain->name)
        ->and($orderItem->domain_type)->toBe('renewal');
});

test('uses custom pricing when domain has custom price', function (): void {
    Date::setTestNow(Date::create(2026, 3, 14));

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

    $service = new DomainInvoiceGenerationService;
    $results = $service->generateRenewalInvoices(7);

    expect($results['generated'])->toBe(1);

    $order = Order::query()->where('user_id', $user->id)->first();
    expect($order->total_amount)->toBe('25.00')
        ->and($order->currency)->toBe('EUR');
});

test('skips domain with existing pending renewal order', function (): void {
    Date::setTestNow(Date::create(2026, 3, 14));

    $user = User::factory()->create();
    $domain = Domain::factory()->create([
        'owner_id' => $user->id,
        'status' => 'active',
        'auto_renew' => true,
        'expires_at' => Date::now()->addDays(5),
        'years' => 1,
    ]);

    // Create existing pending order
    $existingOrder = Order::factory()->pending()->create([
        'user_id' => $user->id,
    ]);
    OrderItem::factory()->create([
        'order_id' => $existingOrder->id,
        'domain_name' => $domain->name,
        'domain_type' => 'renewal',
    ]);

    $service = new DomainInvoiceGenerationService;
    $results = $service->generateRenewalInvoices(7);

    expect($results['generated'])->toBe(0);
});

test('skips domains without auto renew', function (): void {
    Date::setTestNow(Date::create(2026, 3, 14));

    $user = User::factory()->create();
    Domain::factory()->create([
        'owner_id' => $user->id,
        'status' => 'active',
        'auto_renew' => false,
        'expires_at' => Date::now()->addDays(5),
    ]);

    $service = new DomainInvoiceGenerationService;
    $results = $service->generateRenewalInvoices(7);

    expect($results['generated'])->toBe(0);
});

test('skips non-active domains', function (): void {
    Date::setTestNow(Date::create(2026, 3, 14));

    $user = User::factory()->create();
    Domain::factory()->create([
        'owner_id' => $user->id,
        'status' => 'expired',
        'auto_renew' => true,
        'expires_at' => Date::now()->addDays(5),
    ]);

    $service = new DomainInvoiceGenerationService;
    $results = $service->generateRenewalInvoices(7);

    expect($results['generated'])->toBe(0);
});

test('skips domains expiring outside the window', function (): void {
    Date::setTestNow(Date::create(2026, 3, 14));

    $user = User::factory()->create();
    Domain::factory()->create([
        'owner_id' => $user->id,
        'status' => 'active',
        'auto_renew' => true,
        'expires_at' => Date::now()->addDays(15),
    ]);

    $service = new DomainInvoiceGenerationService;
    $results = $service->generateRenewalInvoices(7);

    expect($results['generated'])->toBe(0);
});
