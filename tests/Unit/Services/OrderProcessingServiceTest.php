<?php

declare(strict_types=1);

use App\Jobs\ProcessDomainRenewalJob;
use App\Jobs\ProcessSubscriptionRenewalJob;
use App\Models\Currency;
use App\Models\Domain;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use App\Services\OrderProcessingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::query()->create(['id' => 1, 'title' => 'Admin']);
    Role::query()->create(['id' => 2, 'title' => 'User']);

    Currency::query()->create([
        'code' => 'USD',
        'name' => 'US Dollar',
        'symbol' => '$',
        'exchange_rate' => 1.0,
        'is_base' => true,
        'is_active' => true,
    ]);

    Queue::fake();

    $this->service = new OrderProcessingService();
});

describe('createOrderItemsFromJson', function (): void {
    it('creates order items from order json when none exist', function (): void {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create([
            'items' => [
                [
                    'id' => 'test-1',
                    'name' => 'example.com',
                    'price' => 10.99,
                    'quantity' => 1,
                    'attributes' => [
                        'type' => 'registration',
                        'currency' => 'USD',
                        'domain_name' => 'example.com',
                    ],
                ],
            ],
        ]);

        $this->service->createOrderItemsFromJson($order);

        expect($order->orderItems)->toHaveCount(1);
        $orderItem = $order->orderItems->first();
        expect($orderItem->domain_name)->toBe('example.com');
        expect($orderItem->domain_type)->toBe('registration');
        expect($orderItem->price)->toBe('10.99');
    });

    it('does not create duplicate order items if they already exist', function (): void {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create([
            'items' => [
                [
                    'id' => 'test-1',
                    'name' => 'example.com',
                    'price' => 10.99,
                    'quantity' => 1,
                    'attributes' => [
                        'type' => 'registration',
                        'currency' => 'USD',
                    ],
                ],
            ],
        ]);

        // Create an order item first
        OrderItem::factory()->for($order)->create([
            'domain_name' => 'example.com',
        ]);

        $initialCount = $order->orderItems()->count();

        $this->service->createOrderItemsFromJson($order);

        expect($order->orderItems()->count())->toBe($initialCount);
    });

    it('includes subscription metadata when creating subscription renewal items', function (): void {
        $user = User::factory()->create();
        $subscription = Subscription::factory()->for($user)->create();

        $order = Order::factory()->for($user)->create([
            'items' => [
                [
                    'id' => 'test-1',
                    'name' => 'Subscription Renewal',
                    'price' => 29.99,
                    'quantity' => 1,
                    'attributes' => [
                        'type' => 'subscription_renewal',
                        'currency' => 'USD',
                        'subscription_id' => $subscription->id,
                        'billing_cycle' => 'monthly',
                        'hosting_plan_id' => 1,
                    ],
                ],
            ],
        ]);

        $this->service->createOrderItemsFromJson($order);

        $orderItem = $order->orderItems->first();
        expect($orderItem->metadata)->toHaveKey('subscription_id', $subscription->id);
        expect($orderItem->metadata)->toHaveKey('billing_cycle', 'monthly');
        expect($orderItem->metadata)->toHaveKey('hosting_plan_id', 1);
    });

    it('handles empty items array gracefully', function (): void {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create([
            'items' => [],
        ]);

        $this->service->createOrderItemsFromJson($order);

        expect($order->orderItems)->toHaveCount(0);
    });
});

describe('dispatchRenewalJobs', function (): void {
    it('dispatches domain renewal job when order has renewal items', function (): void {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create([
            'type' => 'renewal',
            'items' => [
                [
                    'id' => 'test-1',
                    'name' => 'example.com',
                    'price' => 10.99,
                    'quantity' => 1,
                    'attributes' => [
                        'type' => 'renewal',
                        'domain_id' => 1,
                    ],
                ],
            ],
        ]);

        $this->service->dispatchRenewalJobs($order);

        Queue::assertPushed(ProcessDomainRenewalJob::class, fn ($job): bool => $job->order->id === $order->id);
    });

    it('dispatches subscription renewal job when order has subscription renewal items', function (): void {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create([
            'type' => 'subscription_renewal',
            'items' => [
                [
                    'id' => 'test-1',
                    'name' => 'Subscription Renewal',
                    'price' => 29.99,
                    'quantity' => 1,
                    'attributes' => [
                        'type' => 'subscription_renewal',
                        'subscription_id' => 1,
                    ],
                ],
            ],
        ]);

        $this->service->dispatchRenewalJobs($order);

        Queue::assertPushed(ProcessSubscriptionRenewalJob::class, fn ($job): bool => $job->order->id === $order->id);
    });

    it('dispatches both jobs when order has mixed renewal items', function (): void {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create([
            'items' => [
                [
                    'id' => 'test-1',
                    'name' => 'example.com',
                    'price' => 10.99,
                    'quantity' => 1,
                    'attributes' => [
                        'type' => 'renewal',
                    ],
                ],
                [
                    'id' => 'test-2',
                    'name' => 'Subscription Renewal',
                    'price' => 29.99,
                    'quantity' => 1,
                    'attributes' => [
                        'type' => 'subscription_renewal',
                    ],
                ],
            ],
        ]);

        $this->service->dispatchRenewalJobs($order);

        Queue::assertPushed(ProcessDomainRenewalJob::class);
        Queue::assertPushed(ProcessSubscriptionRenewalJob::class);
    });

    it('does not dispatch jobs when order has no renewal items', function (): void {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create([
            'items' => [
                [
                    'id' => 'test-1',
                    'name' => 'example.com',
                    'price' => 10.99,
                    'quantity' => 1,
                    'attributes' => [
                        'type' => 'registration',
                    ],
                ],
            ],
        ]);

        $this->service->dispatchRenewalJobs($order);

        Queue::assertNothingPushed();
    });
});

describe('getServiceDetailsRedirectUrl', function (): void {
    it('returns domain info route for domain renewal orders', function (): void {
        $user = User::factory()->create();
        $domain = Domain::factory()->create(['owner_id' => $user->id]);
        $order = Order::factory()->for($user)->create();

        OrderItem::factory()->for($order)->create([
            'domain_type' => 'renewal',
            'domain_id' => $domain->id,
        ]);

        $url = $this->service->getServiceDetailsRedirectUrl($order);

        expect($url)->toBe(route('admin.domain.info', $domain));
    });

    it('returns subscription show route for subscription renewal orders', function (): void {
        $user = User::factory()->create();
        $subscription = Subscription::factory()->for($user)->create();
        $order = Order::factory()->for($user)->create();

        OrderItem::factory()->for($order)->create([
            'domain_type' => 'subscription_renewal',
            'metadata' => ['subscription_id' => $subscription->id],
        ]);

        $url = $this->service->getServiceDetailsRedirectUrl($order);

        expect($url)->toBe(route('admin.products.subscription.show', $subscription));
    });

    it('prioritizes domain renewals over subscription renewals', function (): void {
        $user = User::factory()->create();
        $domain = Domain::factory()->create(['owner_id' => $user->id]);
        $subscription = Subscription::factory()->for($user)->create();
        $order = Order::factory()->for($user)->create();

        OrderItem::factory()->for($order)->create([
            'domain_type' => 'renewal',
            'domain_id' => $domain->id,
        ]);

        OrderItem::factory()->for($order)->create([
            'domain_type' => 'subscription_renewal',
            'metadata' => ['subscription_id' => $subscription->id],
        ]);

        $url = $this->service->getServiceDetailsRedirectUrl($order);

        expect($url)->toBe(route('admin.domain.info', $domain));
    });

    it('returns billing show route when no order items exist', function (): void {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create();

        $url = $this->service->getServiceDetailsRedirectUrl($order);

        expect($url)->toBe(route('billing.show', $order));
    });

    it('returns billing show route as fallback', function (): void {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create();

        OrderItem::factory()->for($order)->create([
            'domain_type' => 'registration',
        ]);

        $url = $this->service->getServiceDetailsRedirectUrl($order);

        expect($url)->toBe(route('billing.show', $order));
    });
});
