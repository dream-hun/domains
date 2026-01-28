<?php

declare(strict_types=1);

use App\Jobs\ProcessSubscriptionRenewalJob;
use App\Models\HostingPlan;
use App\Models\HostingPlanPrice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Subscription;
use App\Models\User;
use App\Services\SubscriptionRenewalService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('subscription renewal uses renewal_price not regular_price', function (): void {
    $user = User::factory()->create();
    $plan = HostingPlan::factory()->create();
    $planPrice = HostingPlanPrice::factory()->create([
        'hosting_plan_id' => $plan->id,
        'billing_cycle' => 'monthly',
        'regular_price' => 12000, // $120.00
        'renewal_price' => 10000, // $100.00 (different)
    ]);

    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'hosting_plan_id' => $plan->id,
        'hosting_plan_price_id' => $planPrice->id,
        'billing_cycle' => 'monthly',
    ]);

    // Create order with renewal_price amount
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'type' => 'subscription_renewal',
        'status' => 'processing',
        'payment_status' => 'paid',
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'domain_type' => 'subscription_renewal',
        'price' => 100.00, // renewal_price, not regular_price
        'total_amount' => 100.00,
        'metadata' => [
            'subscription_id' => $subscription->id,
            'billing_cycle' => 'monthly',
        ],
    ]);

    // Process renewal
    $job = new ProcessSubscriptionRenewalJob($order);
    $job->handle(resolve(SubscriptionRenewalService::class));

    $subscription->refresh();
    expect($subscription->status)->toBe('active');
});

test('subscription renewal validates billing cycle consistency', function (): void {
    $user = User::factory()->create();
    $plan = HostingPlan::factory()->create();
    $monthlyPrice = HostingPlanPrice::factory()->create([
        'hosting_plan_id' => $plan->id,
        'billing_cycle' => 'monthly',
        'renewal_price' => 10000,
    ]);
    $annualPrice = HostingPlanPrice::factory()->create([
        'hosting_plan_id' => $plan->id,
        'billing_cycle' => 'annually',
        'renewal_price' => 100000,
    ]);

    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'hosting_plan_id' => $plan->id,
        'hosting_plan_price_id' => $monthlyPrice->id,
        'billing_cycle' => 'monthly',
    ]);

    // Create order with annual billing cycle (different from subscription's stored cycle)
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'type' => 'subscription_renewal',
        'status' => 'processing',
        'payment_status' => 'paid',
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'domain_type' => 'subscription_renewal',
        'price' => 100.00, // Monthly price
        'quantity' => 12, // 12 months for annual
        'total_amount' => 1200.00, // $100/month × 12 months = $1200
        'metadata' => [
            'subscription_id' => $subscription->id,
            'billing_cycle' => 'annually', // Changed from monthly
        ],
    ]);

    $order->refresh();

    // Process renewal - should update billing cycle
    $job = new ProcessSubscriptionRenewalJob($order);
    $job->handle(resolve(SubscriptionRenewalService::class));

    $subscription->refresh();
    expect($subscription->billing_cycle)->toBe('annually')
        ->and($subscription->status)->toBe('active');
});

test('subscription renewal rejects underpayment', function (): void {
    $user = User::factory()->create();
    $plan = HostingPlan::factory()->create();
    $planPrice = HostingPlanPrice::factory()->create([
        'hosting_plan_id' => $plan->id,
        'billing_cycle' => 'monthly',
        'renewal_price' => 10000, // $100.00
    ]);

    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'hosting_plan_id' => $plan->id,
        'hosting_plan_price_id' => $planPrice->id,
        'billing_cycle' => 'monthly',
    ]);

    // Create order with incorrect (lower) amount
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'type' => 'subscription_renewal',
        'status' => 'processing',
        'payment_status' => 'paid',
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'domain_type' => 'subscription_renewal',
        'price' => 50.00, // Only $50, should be $100
        'total_amount' => 50.00,
        'metadata' => [
            'subscription_id' => $subscription->id,
            'billing_cycle' => 'monthly',
        ],
    ]);

    // Process renewal - should fail
    $job = new ProcessSubscriptionRenewalJob($order);
    $job->handle(resolve(SubscriptionRenewalService::class));

    $order->refresh();
    expect($order->status)->toBe('failed')
        ->and($order->notes)->toContain('failed');
});

test('subscription renewal updates product snapshot', function (): void {
    $user = User::factory()->create();
    $plan = HostingPlan::factory()->create();
    $planPrice = HostingPlanPrice::factory()->create([
        'hosting_plan_id' => $plan->id,
        'billing_cycle' => 'monthly',
        'renewal_price' => 10000,
    ]);

    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'hosting_plan_id' => $plan->id,
        'hosting_plan_price_id' => $planPrice->id,
        'billing_cycle' => 'monthly',
        'product_snapshot' => [
            'plan' => [
                'id' => $plan->id,
                'name' => $plan->name,
            ],
            'price' => [
                'id' => $planPrice->id,
                'regular_price' => $planPrice->regular_price,
                'renewal_price' => $planPrice->renewal_price,
                'billing_cycle' => $planPrice->billing_cycle,
            ],
        ],
    ]);

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'type' => 'subscription_renewal',
        'status' => 'processing',
        'payment_status' => 'paid',
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'domain_type' => 'subscription_renewal',
        'price' => 100.00,
        'total_amount' => 100.00,
        'metadata' => [
            'subscription_id' => $subscription->id,
            'billing_cycle' => 'monthly',
        ],
    ]);

    $order->refresh();

    $job = new ProcessSubscriptionRenewalJob($order);
    $job->handle(resolve(SubscriptionRenewalService::class));

    $subscription->refresh();
    $snapshot = $subscription->product_snapshot;

    expect($snapshot)->toHaveKey('renewals')
        ->and($snapshot['renewals'])->toBeArray()
        ->and(count($snapshot['renewals']))->toBe(1);
});

test('subscription renewal with annually extends expiry by 1 year', function (): void {
    $user = User::factory()->create();
    $plan = HostingPlan::factory()->create();

    // Create monthly price (required for validation)
    $monthlyPrice = HostingPlanPrice::factory()->create([
        'hosting_plan_id' => $plan->id,
        'billing_cycle' => 'monthly',
        'renewal_price' => 10000, // $100.00 per month
    ]);

    $annualPrice = HostingPlanPrice::factory()->create([
        'hosting_plan_id' => $plan->id,
        'billing_cycle' => 'annually',
        'renewal_price' => 100000, // $1000.00
    ]);

    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'hosting_plan_id' => $plan->id,
        'hosting_plan_price_id' => $monthlyPrice->id,
        'billing_cycle' => 'monthly',
        'expires_at' => now()->addMonth(),
    ]);

    $originalExpiry = $subscription->expires_at->copy();

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'type' => 'subscription_renewal',
        'status' => 'processing',
        'payment_status' => 'paid',
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'domain_type' => 'subscription_renewal',
        'price' => 100.00, // Monthly price
        'quantity' => 12, // 12 months for annual
        'total_amount' => 1200.00, // $100/month × 12 months = $1200
        'metadata' => [
            'subscription_id' => $subscription->id,
            'billing_cycle' => 'annually', // 1 year
        ],
    ]);

    $order->refresh();

    $job = new ProcessSubscriptionRenewalJob($order);
    $job->handle(resolve(SubscriptionRenewalService::class));

    $subscription->refresh();

    // Should extend by 1 year (12 months)
    $expectedExpiry = $originalExpiry->copy()->addMonths(12);
    expect($subscription->expires_at->format('Y-m-d'))->toBe($expectedExpiry->format('Y-m-d'))
        ->and($subscription->billing_cycle)->toBe('annually');
});
