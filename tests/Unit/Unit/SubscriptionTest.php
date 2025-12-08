<?php

declare(strict_types=1);

use App\Enums\Hosting\BillingCycle;
use App\Models\HostingPlan;
use App\Models\HostingPlanPrice;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('isExpiringSoon returns true for subscriptions expiring within specified days', function (): void {
    $subscription = Subscription::factory()->create([
        'status' => 'active',
        'expires_at' => now()->addDays(5),
    ]);

    expect($subscription->isExpiringSoon(7))->toBeTrue()
        ->and($subscription->isExpiringSoon(5))->toBeTrue()
        ->and($subscription->isExpiringSoon(3))->toBeFalse();
});

test('isExpiringSoon returns false for inactive subscriptions', function (): void {
    $subscription = Subscription::factory()->create([
        'status' => 'cancelled',
        'expires_at' => now()->addDays(5),
    ]);

    expect($subscription->isExpiringSoon(7))->toBeFalse();
});

test('canBeRenewed returns true for active subscriptions', function (): void {
    $subscription = Subscription::factory()->create([
        'status' => 'active',
    ]);

    expect($subscription->canBeRenewed())->toBeTrue();
});

test('canBeRenewed returns true for expired subscriptions', function (): void {
    $subscription = Subscription::factory()->create([
        'status' => 'expired',
    ]);

    expect($subscription->canBeRenewed())->toBeTrue();
});

test('canBeRenewed returns false for cancelled subscriptions', function (): void {
    $subscription = Subscription::factory()->create([
        'status' => 'cancelled',
    ]);

    expect($subscription->canBeRenewed())->toBeFalse();
});

test('extendSubscription extends expiry by monthly billing cycle', function (): void {
    $originalExpiry = now()->addDays(10);
    $subscription = Subscription::factory()->create([
        'status' => 'active',
        'billing_cycle' => 'monthly',
        'expires_at' => $originalExpiry,
    ]);

    $subscription->extendSubscription(BillingCycle::Monthly);

    $expectedNewExpiry = $originalExpiry->copy()->addMonth();

    expect($subscription->expires_at->format('Y-m-d'))->toBe($expectedNewExpiry->format('Y-m-d'))
        ->and($subscription->status)->toBe('active');
});

test('extendSubscription extends expiry by annual billing cycle', function (): void {
    $originalExpiry = now()->addMonth();
    $subscription = Subscription::factory()->create([
        'status' => 'expired',
        'billing_cycle' => 'annually',
        'expires_at' => $originalExpiry,
    ]);

    $subscription->extendSubscription(BillingCycle::Annually);

    $expectedNewExpiry = $originalExpiry->copy()->addYear();

    expect($subscription->expires_at->format('Y-m-d'))->toBe($expectedNewExpiry->format('Y-m-d'))
        ->and($subscription->status)->toBe('active');
});

test('extendSubscription reactivates expired subscription', function (): void {
    $subscription = Subscription::factory()->create([
        'status' => 'expired',
        'billing_cycle' => 'monthly',
        'expires_at' => now()->subDays(5),
    ]);

    $subscription->extendSubscription(BillingCycle::Monthly);

    expect($subscription->status)->toBe('active');
});

test('subscription belongs to user', function (): void {
    $user = User::factory()->create();
    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
    ]);

    expect($subscription->user)->toBeInstanceOf(User::class)
        ->and($subscription->user->id)->toBe($user->id);
});

test('subscription belongs to hosting plan', function (): void {
    $plan = HostingPlan::factory()->create(['name' => 'Test Plan']);
    $subscription = Subscription::factory()->create([
        'hosting_plan_id' => $plan->id,
    ]);

    expect($subscription->plan)->toBeInstanceOf(HostingPlan::class)
        ->and($subscription->plan->name)->toBe('Test Plan');
});

test('subscription belongs to hosting plan price', function (): void {
    $plan = HostingPlan::factory()->create();
    $planPrice = HostingPlanPrice::factory()->create([
        'hosting_plan_id' => $plan->id,
    ]);

    $subscription = Subscription::factory()->create([
        'hosting_plan_id' => $plan->id,
        'hosting_plan_price_id' => $planPrice->id,
    ]);

    expect($subscription->planPrice)->toBeInstanceOf(HostingPlanPrice::class)
        ->and($subscription->planPrice->id)->toBe($planPrice->id);
});

test('expiringSoon scope returns subscriptions expiring within specified days', function (): void {
    $expiringSoon = Subscription::factory()->create([
        'status' => 'active',
        'expires_at' => now()->addDays(15),
    ]);

    $notExpiring = Subscription::factory()->create([
        'status' => 'active',
        'expires_at' => now()->addDays(60),
    ]);

    $results = Subscription::query()->expiringSoon(30)->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($expiringSoon->id);
});

test('autoRenewable scope returns subscriptions with auto_renew enabled', function (): void {
    $autoRenew = Subscription::factory()->create([
        'status' => 'active',
        'auto_renew' => true,
    ]);

    $manual = Subscription::factory()->create([
        'status' => 'active',
        'auto_renew' => false,
    ]);

    $results = Subscription::query()->autoRenewable()->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($autoRenew->id);
});
