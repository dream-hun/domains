<?php

declare(strict_types=1);

use App\Jobs\ProcessDomainRenewalJob;
use App\Jobs\ProcessSubscriptionRenewalJob;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\OrderJobDispatcherService;
use App\Services\OrderProcessingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;

uses(RefreshDatabase::class);

test('dispatchJobsForOrder dispatches both domain and subscription renewal jobs when order has both item types', function (): void {
    Bus::fake();

    $user = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'type' => 'renewal',
        'items' => [
            [
                'id' => 'renewal-1',
                'name' => 'example.com (Renewal)',
                'price' => 10,
                'quantity' => 1,
                'attributes' => [
                    'type' => 'renewal',
                    'domain_id' => 1,
                    'domain_name' => 'example.com',
                    'years' => 1,
                ],
            ],
            [
                'id' => 2,
                'name' => 'Hosting - Plan (Renewal)',
                'price' => 5,
                'quantity' => 1,
                'attributes' => [
                    'type' => 'subscription_renewal',
                    'subscription_id' => 1,
                    'billing_cycle' => 'monthly',
                ],
            ],
        ],
    ]);

    OrderItem::query()->create([
        'order_id' => $order->id,
        'domain_name' => 'example.com',
        'domain_type' => 'renewal',
        'domain_id' => null,
        'price' => 10,
        'currency' => 'USD',
        'quantity' => 1,
        'years' => 1,
        'total_amount' => 10,
        'metadata' => [],
    ]);
    OrderItem::query()->create([
        'order_id' => $order->id,
        'domain_name' => 'Hosting',
        'domain_type' => 'subscription_renewal',
        'domain_id' => null,
        'price' => 5,
        'currency' => 'USD',
        'quantity' => 1,
        'years' => 1,
        'total_amount' => 5,
        'metadata' => ['subscription_id' => 1, 'billing_cycle' => 'monthly'],
    ]);

    $service = resolve(OrderJobDispatcherService::class);
    $service->dispatchJobsForOrder($order, []);

    Bus::assertDispatched(ProcessDomainRenewalJob::class);
    Bus::assertDispatched(ProcessSubscriptionRenewalJob::class);
});

test('dispatchJobsForOrder dispatches only domain renewal job when order has only renewal items', function (): void {
    Bus::fake();

    $user = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'type' => 'renewal',
        'items' => [
            [
                'id' => 'renewal-1',
                'name' => 'example.com (Renewal)',
                'price' => 10,
                'quantity' => 1,
                'attributes' => [
                    'type' => 'renewal',
                    'domain_id' => null,
                    'domain_name' => 'example.com',
                    'years' => 1,
                ],
            ],
        ],
    ]);

    $orderProcessingService = resolve(OrderProcessingService::class);
    $orderProcessingService->createOrderItemsFromJson($order);

    $service = resolve(OrderJobDispatcherService::class);
    $service->dispatchJobsForOrder($order, []);

    Bus::assertDispatched(ProcessDomainRenewalJob::class);
    Bus::assertNotDispatched(ProcessSubscriptionRenewalJob::class);
});

test('dispatchJobsForOrder dispatches only subscription renewal job when order has only subscription_renewal items', function (): void {
    Bus::fake();

    $user = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'type' => 'subscription_renewal',
        'items' => [
            [
                'id' => 1,
                'name' => 'Hosting - Plan (Renewal)',
                'price' => 5,
                'quantity' => 1,
                'attributes' => [
                    'type' => 'subscription_renewal',
                    'subscription_id' => 1,
                    'billing_cycle' => 'monthly',
                ],
            ],
        ],
    ]);

    $orderProcessingService = resolve(OrderProcessingService::class);
    $orderProcessingService->createOrderItemsFromJson($order);

    $service = resolve(OrderJobDispatcherService::class);
    $service->dispatchJobsForOrder($order, []);

    Bus::assertNotDispatched(ProcessDomainRenewalJob::class);
    Bus::assertDispatched(ProcessSubscriptionRenewalJob::class);
});
