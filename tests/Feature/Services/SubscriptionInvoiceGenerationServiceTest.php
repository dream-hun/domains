<?php

declare(strict_types=1);

use App\Jobs\GenerateSubscriptionRenewalInvoiceJob;
use App\Models\Subscription;
use App\Models\User;
use App\Services\SubscriptionInvoiceGenerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

test('dispatches renewal invoice job for subscription expiring within window', function (): void {
    Date::setTestNow(Date::create(2026, 3, 14));
    Queue::fake();

    $user = User::factory()->create();
    $subscription = Subscription::factory()->active()->autoRenew()->create([
        'user_id' => $user->id,
        'next_renewal_at' => Date::now()->addDays(5),
        'billing_cycle' => 'monthly',
    ]);

    $service = new SubscriptionInvoiceGenerationService;
    $results = $service->generateRenewalInvoices(7);

    expect($results['dispatched'])->toBe(1)
        ->and($results['skipped'])->toBe(0);

    Queue::assertPushed(GenerateSubscriptionRenewalInvoiceJob::class, fn ($job): bool => $job->subscription->id === $subscription->id);
});

test('dispatches job for subscriptions without auto renew', function (): void {
    Date::setTestNow(Date::create(2026, 3, 14));
    Queue::fake();

    $user = User::factory()->create();
    $subscription = Subscription::factory()->active()->create([
        'user_id' => $user->id,
        'auto_renew' => false,
        'next_renewal_at' => Date::now()->addDays(5),
        'billing_cycle' => 'monthly',
    ]);

    $service = new SubscriptionInvoiceGenerationService;
    $results = $service->generateRenewalInvoices(7);

    expect($results['dispatched'])->toBe(1);

    Queue::assertPushed(GenerateSubscriptionRenewalInvoiceJob::class, fn ($job): bool => $job->subscription->id === $subscription->id);
});

test('skips non-active subscriptions', function (): void {
    Date::setTestNow(Date::create(2026, 3, 14));
    Queue::fake();

    $user = User::factory()->create();
    Subscription::factory()->expired()->autoRenew()->create([
        'user_id' => $user->id,
        'next_renewal_at' => Date::now()->addDays(5),
        'billing_cycle' => 'monthly',
    ]);

    $service = new SubscriptionInvoiceGenerationService;
    $results = $service->generateRenewalInvoices(7);

    expect($results['dispatched'])->toBe(0);

    Queue::assertNothingPushed();
});
