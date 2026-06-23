<?php

declare(strict_types=1);

use App\Enums\Hosting\BillingCycle;
use App\Models\Currency;
use App\Models\HostingPlan;
use App\Models\HostingPlanPrice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Subscription;
use App\Models\User;
use App\Services\SubscriptionRenewalService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// getRenewalPriceInCurrency
// ---------------------------------------------------------------------------

test('getRenewalPriceInCurrency returns the stored price for the requested currency', function (): void {
    $rwf = Currency::query()->where('code', 'RWF')->firstOrFail();
    $plan = HostingPlan::factory()->create();

    HostingPlanPrice::factory()->create([
        'hosting_plan_id' => $plan->id,
        'currency_id' => $rwf->id,
        'billing_cycle' => BillingCycle::Monthly->value,
        'renewal_price' => 14000,
        'is_current' => true,
        'status' => 'active',
    ]);

    $subscription = Subscription::factory()->create([
        'hosting_plan_id' => $plan->id,
        'billing_cycle' => BillingCycle::Monthly->value,
    ]);

    expect($subscription->getRenewalPriceInCurrency('RWF'))->toBe(14000.0);
});

test('getRenewalPriceInCurrency falls back to the base price when no row exists for the currency', function (): void {
    $usd = Currency::query()->where('code', 'USD')->firstOrFail();
    $plan = HostingPlan::factory()->create();

    $usdPrice = HostingPlanPrice::factory()->create([
        'hosting_plan_id' => $plan->id,
        'currency_id' => $usd->id,
        'billing_cycle' => BillingCycle::Monthly->value,
        'renewal_price' => 10.00,
        'is_current' => true,
        'status' => 'active',
    ]);

    // Link to the explicit USD price so getRenewalPrice() returns 10.00 as the fallback
    $subscription = Subscription::factory()->create([
        'hosting_plan_id' => $plan->id,
        'hosting_plan_pricing_id' => $usdPrice->id,
        'billing_cycle' => BillingCycle::Monthly->value,
    ]);

    // No EUR price exists → falls back to the USD base price
    expect($subscription->getRenewalPriceInCurrency('EUR'))->toBe(10.0);
});

test('getRenewalPriceInCurrency returns custom_price regardless of requested currency', function (): void {
    $subscription = Subscription::factory()->create([
        'is_custom_price' => true,
        'custom_price' => 25000,
        'custom_price_currency' => 'RWF',
        'billing_cycle' => BillingCycle::Monthly->value,
    ]);

    expect($subscription->getRenewalPriceInCurrency('RWF'))->toBe(25000.0);
});

// ---------------------------------------------------------------------------
// getMonthlyRenewalPrice
// ---------------------------------------------------------------------------

test('getMonthlyRenewalPrice returns monthly plan price in the requested currency', function (): void {
    $rwf = Currency::query()->where('code', 'RWF')->firstOrFail();
    $plan = HostingPlan::factory()->create();

    HostingPlanPrice::factory()->create([
        'hosting_plan_id' => $plan->id,
        'currency_id' => $rwf->id,
        'billing_cycle' => BillingCycle::Monthly->value,
        'renewal_price' => 14000,
        'is_current' => true,
        'status' => 'active',
    ]);

    $subscription = Subscription::factory()->create([
        'hosting_plan_id' => $plan->id,
        'billing_cycle' => BillingCycle::Monthly->value,
    ]);

    expect($subscription->getMonthlyRenewalPrice('RWF'))->toBe(14000.0);
});

test('getMonthlyRenewalPrice divides custom annual price by 12 for monthly equivalent', function (): void {
    $subscription = Subscription::factory()->create([
        'is_custom_price' => true,
        'custom_price' => 120000,
        'custom_price_currency' => 'RWF',
        'billing_cycle' => BillingCycle::Annually->value,
    ]);

    expect($subscription->getMonthlyRenewalPrice('RWF'))->toBe(10000.0);
});

// ---------------------------------------------------------------------------
// extendSubscription — currency-aware payment validation
// ---------------------------------------------------------------------------

test('extendSubscription accepts a valid non-USD payment matched to the stored currency price', function (): void {
    $rwf = Currency::query()->where('code', 'RWF')->firstOrFail();
    $plan = HostingPlan::factory()->create();

    HostingPlanPrice::factory()->create([
        'hosting_plan_id' => $plan->id,
        'currency_id' => $rwf->id,
        'billing_cycle' => BillingCycle::Monthly->value,
        'renewal_price' => 14000,
        'is_current' => true,
        'status' => 'active',
    ]);

    $subscription = Subscription::factory()->active()->create([
        'hosting_plan_id' => $plan->id,
        'billing_cycle' => BillingCycle::Monthly->value,
        'expires_at' => now()->addMonth(),
    ]);

    $subscription->extendSubscription(
        BillingCycle::Monthly,
        paidAmount: 14000.0,
        paidCurrency: 'RWF',
    );

    expect($subscription->fresh()->status)->toBe('active');
});

test('extendSubscription rejects a USD amount when the invoice was in RWF', function (): void {
    $rwf = Currency::query()->where('code', 'RWF')->firstOrFail();
    $plan = HostingPlan::factory()->create();

    HostingPlanPrice::factory()->create([
        'hosting_plan_id' => $plan->id,
        'currency_id' => $rwf->id,
        'billing_cycle' => BillingCycle::Monthly->value,
        'renewal_price' => 14000,
        'is_current' => true,
        'status' => 'active',
    ]);

    $subscription = Subscription::factory()->active()->create([
        'hosting_plan_id' => $plan->id,
        'billing_cycle' => BillingCycle::Monthly->value,
        'expires_at' => now()->addMonth(),
    ]);

    expect(fn () => $subscription->extendSubscription(
        BillingCycle::Monthly,
        paidAmount: 10.0,
        paidCurrency: 'RWF',
    ))->toThrow(Exception::class, 'Payment amount mismatch');
});

// ---------------------------------------------------------------------------
// extendSubscriptionByMonths — currency-aware payment validation
// ---------------------------------------------------------------------------

test('extendSubscriptionByMonths accepts correct multi-month total in non-USD currency', function (): void {
    $rwf = Currency::query()->where('code', 'RWF')->firstOrFail();
    $plan = HostingPlan::factory()->create();

    HostingPlanPrice::factory()->create([
        'hosting_plan_id' => $plan->id,
        'currency_id' => $rwf->id,
        'billing_cycle' => BillingCycle::Monthly->value,
        'renewal_price' => 14000,
        'is_current' => true,
        'status' => 'active',
    ]);

    $subscription = Subscription::factory()->active()->create([
        'hosting_plan_id' => $plan->id,
        'billing_cycle' => BillingCycle::Monthly->value,
        'expires_at' => now()->addMonth(),
    ]);

    $originalExpiry = $subscription->expires_at->copy();

    $subscription->extendSubscriptionByMonths(3, paidAmount: 42000.0, paidCurrency: 'RWF');

    expect($subscription->fresh()->expires_at->toDateString())
        ->toBe($originalExpiry->addMonths(3)->toDateString());
});

test('extendSubscriptionByMonths rejects an underpayment in non-USD currency', function (): void {
    $rwf = Currency::query()->where('code', 'RWF')->firstOrFail();
    $plan = HostingPlan::factory()->create();

    HostingPlanPrice::factory()->create([
        'hosting_plan_id' => $plan->id,
        'currency_id' => $rwf->id,
        'billing_cycle' => BillingCycle::Monthly->value,
        'renewal_price' => 14000,
        'is_current' => true,
        'status' => 'active',
    ]);

    $subscription = Subscription::factory()->active()->create([
        'hosting_plan_id' => $plan->id,
        'billing_cycle' => BillingCycle::Monthly->value,
        'expires_at' => now()->addMonth(),
    ]);

    expect(fn () => $subscription->extendSubscriptionByMonths(3, paidAmount: 30.0, paidCurrency: 'RWF'))
        ->toThrow(Exception::class, 'Payment amount mismatch');
});

// ---------------------------------------------------------------------------
// SubscriptionRenewalService end-to-end
// ---------------------------------------------------------------------------

test('SubscriptionRenewalService renews successfully when the order item is in a non-USD currency', function (): void {
    $rwf = Currency::query()->where('code', 'RWF')->firstOrFail();
    $plan = HostingPlan::factory()->create();

    $rwfPrice = HostingPlanPrice::factory()->create([
        'hosting_plan_id' => $plan->id,
        'currency_id' => $rwf->id,
        'billing_cycle' => BillingCycle::Monthly->value,
        'renewal_price' => 14000,
        'is_current' => true,
        'status' => 'active',
    ]);

    $user = User::factory()->create();
    $subscription = Subscription::factory()->active()->create([
        'user_id' => $user->id,
        'hosting_plan_id' => $plan->id,
        'hosting_plan_pricing_id' => $rwfPrice->id,
        'billing_cycle' => BillingCycle::Monthly->value,
        'expires_at' => now()->addMonth(),
    ]);

    $order = Order::factory()->paid()->create([
        'user_id' => $user->id,
        'currency' => 'RWF',
        'type' => 'subscription_renewal',
        'items' => [],
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'domain_type' => 'subscription_renewal',
        'currency' => 'RWF',
        'quantity' => 1,
        'total_amount' => 14000.0,
        'metadata' => [
            'subscription_id' => $subscription->id,
            'billing_cycle' => BillingCycle::Monthly->value,
        ],
    ]);

    $result = (new SubscriptionRenewalService)->processSubscriptionRenewals($order);

    expect($result['successful'])->toHaveCount(1)
        ->and($result['failed'])->toHaveCount(0);
});

test('SubscriptionRenewalService fails when the paid amount does not match the stored RWF price', function (): void {
    $rwf = Currency::query()->where('code', 'RWF')->firstOrFail();
    $plan = HostingPlan::factory()->create();

    $rwfPrice = HostingPlanPrice::factory()->create([
        'hosting_plan_id' => $plan->id,
        'currency_id' => $rwf->id,
        'billing_cycle' => BillingCycle::Monthly->value,
        'renewal_price' => 14000,
        'is_current' => true,
        'status' => 'active',
    ]);

    $user = User::factory()->create();
    $subscription = Subscription::factory()->active()->create([
        'user_id' => $user->id,
        'hosting_plan_id' => $plan->id,
        'hosting_plan_pricing_id' => $rwfPrice->id,
        'billing_cycle' => BillingCycle::Monthly->value,
        'expires_at' => now()->addMonth(),
    ]);

    $order = Order::factory()->paid()->create([
        'user_id' => $user->id,
        'currency' => 'RWF',
        'type' => 'subscription_renewal',
        'items' => [],
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'domain_type' => 'subscription_renewal',
        'currency' => 'RWF',
        'quantity' => 1,
        'total_amount' => 10.0, // USD-scale amount in an RWF order
        'metadata' => [
            'subscription_id' => $subscription->id,
            'billing_cycle' => BillingCycle::Monthly->value,
        ],
    ]);

    $result = (new SubscriptionRenewalService)->processSubscriptionRenewals($order);

    expect($result['successful'])->toHaveCount(0)
        ->and($result['failed'])->toHaveCount(1);
});
