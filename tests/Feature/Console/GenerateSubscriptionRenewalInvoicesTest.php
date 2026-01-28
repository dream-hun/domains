<?php

declare(strict_types=1);

use App\Models\Currency;
use App\Models\HostingPlan;
use App\Models\HostingPlanPrice;
use App\Models\Order;
use App\Models\Subscription;
use App\Models\User;
use App\Services\SubscriptionInvoiceGenerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Create currencies
    Currency::query()->create([
        'code' => 'USD',
        'name' => 'US Dollar',
        'symbol' => '$',
        'exchange_rate' => 1.0,
        'is_base' => true,
        'is_active' => true,
    ]);

    Currency::query()->create([
        'code' => 'RWF',
        'name' => 'Rwandan Franc',
        'symbol' => 'Fr',
        'exchange_rate' => 1200.0,
        'is_base' => false,
        'is_active' => true,
    ]);

    $this->user = User::factory()->create();
    $this->plan = HostingPlan::factory()->create();
    $this->planPrice = HostingPlanPrice::factory()->create([
        'hosting_plan_id' => $this->plan->id,
        'billing_cycle' => 'monthly',
        'renewal_price' => 10000, // $100.00 in cents
    ]);
});

test('command generates invoices for subscriptions due for renewal', function (): void {
    $subscription = Subscription::factory()->create([
        'user_id' => $this->user->id,
        'hosting_plan_id' => $this->plan->id,
        'hosting_plan_price_id' => $this->planPrice->id,
        'status' => 'active',
        'auto_renew' => true,
        'billing_cycle' => 'monthly',
        'next_renewal_at' => now()->addDays(5), // Within 7 days
        'last_invoice_generated_at' => null,
    ]);

    $service = resolve(SubscriptionInvoiceGenerationService::class);
    $results = $service->generateRenewalInvoices(7);

    expect($results['generated'])->toBe(1)
        ->and($results['failed'])->toBeEmpty();

    $order = Order::query()
        ->where('user_id', $this->user->id)
        ->where('type', 'subscription_renewal')
        ->first();

    expect($order)->not->toBeNull()
        ->and($order->payment_status)->toBe('pending')
        ->and($order->status)->toBe('pending');

    $subscription->refresh();
    expect($subscription->last_invoice_generated_at)->not->toBeNull();
});

test('command respects auto_renew flag', function (): void {
    $subscription = Subscription::factory()->create([
        'user_id' => $this->user->id,
        'hosting_plan_id' => $this->plan->id,
        'hosting_plan_price_id' => $this->planPrice->id,
        'status' => 'active',
        'auto_renew' => false, // Disabled
        'billing_cycle' => 'monthly',
        'next_renewal_at' => now()->addDays(5),
        'last_invoice_generated_at' => null,
    ]);

    $service = resolve(SubscriptionInvoiceGenerationService::class);
    $results = $service->generateRenewalInvoices(7);

    expect($results['generated'])->toBe(0);
});

test('command uses custom price when applicable', function (): void {
    $subscription = Subscription::factory()->create([
        'user_id' => $this->user->id,
        'hosting_plan_id' => $this->plan->id,
        'hosting_plan_price_id' => $this->planPrice->id,
        'status' => 'active',
        'auto_renew' => true,
        'billing_cycle' => 'monthly',
        'is_custom_price' => true,
        'custom_price' => 75.50,
        'custom_price_currency' => 'USD',
        'next_renewal_at' => now()->addDays(5),
        'last_invoice_generated_at' => null,
    ]);

    $service = resolve(SubscriptionInvoiceGenerationService::class);
    $results = $service->generateRenewalInvoices(7);

    expect($results['generated'])->toBe(1);

    $order = Order::query()
        ->where('user_id', $this->user->id)
        ->where('type', 'subscription_renewal')
        ->first();

    expect($order->total_amount)->toBe('75.50');
});

test('command uses correct currency for custom price subscriptions', function (): void {
    $subscription = Subscription::factory()->create([
        'user_id' => $this->user->id,
        'hosting_plan_id' => $this->plan->id,
        'hosting_plan_price_id' => $this->planPrice->id,
        'status' => 'active',
        'auto_renew' => true,
        'billing_cycle' => 'monthly',
        'is_custom_price' => true,
        'custom_price' => 100.00, // Stored in original currency (RWF)
        'custom_price_currency' => 'RWF',
        'next_renewal_at' => now()->addDays(5),
        'last_invoice_generated_at' => null,
    ]);

    $service = resolve(SubscriptionInvoiceGenerationService::class);
    $results = $service->generateRenewalInvoices(7);

    expect($results['generated'])->toBe(1);

    $order = Order::query()
        ->where('user_id', $this->user->id)
        ->where('type', 'subscription_renewal')
        ->first();

    expect($order->currency)->toBe('RWF');
});

test('command does not generate duplicate invoices', function (): void {
    $subscription = Subscription::factory()->create([
        'user_id' => $this->user->id,
        'hosting_plan_id' => $this->plan->id,
        'hosting_plan_price_id' => $this->planPrice->id,
        'status' => 'active',
        'auto_renew' => true,
        'billing_cycle' => 'monthly',
        'next_renewal_at' => now()->addDays(5),
        'last_invoice_generated_at' => now()->subDays(1), // Already generated recently
    ]);

    $service = resolve(SubscriptionInvoiceGenerationService::class);
    $results = $service->generateRenewalInvoices(7);

    expect($results['generated'])->toBe(0);
});

test('command creates orders with correct status and currency', function (): void {
    $subscription = Subscription::factory()->create([
        'user_id' => $this->user->id,
        'hosting_plan_id' => $this->plan->id,
        'hosting_plan_price_id' => $this->planPrice->id,
        'status' => 'active',
        'auto_renew' => true,
        'billing_cycle' => 'monthly',
        'next_renewal_at' => now()->addDays(5),
        'last_invoice_generated_at' => null,
    ]);

    $service = resolve(SubscriptionInvoiceGenerationService::class);
    $service->generateRenewalInvoices(7);

    $order = Order::query()
        ->where('user_id', $this->user->id)
        ->where('type', 'subscription_renewal')
        ->first();

    expect($order)->not->toBeNull()
        ->and($order->status)->toBe('pending')
        ->and($order->payment_status)->toBe('pending')
        ->and($order->currency)->toBe('USD');

    $orderItem = $order->orderItems()->first();
    expect($orderItem)->not->toBeNull()
        ->and($orderItem->currency)->toBe('USD')
        ->and($orderItem->domain_type)->toBe('subscription_renewal');
});

test('order items have correct currency and exchange rate', function (): void {
    $subscription = Subscription::factory()->create([
        'user_id' => $this->user->id,
        'hosting_plan_id' => $this->plan->id,
        'hosting_plan_price_id' => $this->planPrice->id,
        'status' => 'active',
        'auto_renew' => true,
        'billing_cycle' => 'monthly',
        'is_custom_price' => true,
        'custom_price' => 100.00,
        'custom_price_currency' => 'RWF',
        'next_renewal_at' => now()->addDays(5),
        'last_invoice_generated_at' => null,
    ]);

    $service = resolve(SubscriptionInvoiceGenerationService::class);
    $service->generateRenewalInvoices(7);

    $order = Order::query()
        ->where('user_id', $this->user->id)
        ->where('type', 'subscription_renewal')
        ->first();

    $orderItem = $order->orderItems()->first();
    expect($orderItem->currency)->toBe('RWF')
        ->and($orderItem->metadata['renewal_currency'])->toBe('RWF')
        ->and($orderItem->metadata['is_custom_price'])->toBeTrue()
        ->and($orderItem->metadata['exchange_rate'])->toBeGreaterThan(0);
});
