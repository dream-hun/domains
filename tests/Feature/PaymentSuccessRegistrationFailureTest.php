<?php

declare(strict_types=1);

use App\Actions\RegisterDomainAction;
use App\Jobs\RetryDomainRegistrationJob;
use App\Models\Contact;
use App\Models\Currency;
use App\Models\Domain;
use App\Models\FailedDomainRegistration;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\DomainRegistrationService;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

use function Pest\Laravel\assertDatabaseHas;

beforeEach(function (): void {
    Queue::fake();
    Notification::fake();

    // Create test currency
    Currency::factory()->create([
        'code' => 'USD',
        'exchange_rate' => 1.0,
    ]);

    // Create test user with contact
    $this->user = User::factory()->create();
    $this->contact = Contact::factory()->create([
        'user_id' => $this->user->id,
        'is_primary' => true,
    ]);
});

it('records failed domain registration and dispatches retry job when registration fails', function (): void {
    // Create order with paid status
    $order = Order::factory()->create([
        'user_id' => $this->user->id,
        'payment_status' => 'paid',
        'status' => 'processing',
    ]);

    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'domain_name' => 'test.rw',
        'years' => 1,
    ]);

    // Mock RegisterDomainAction to return failure
    $mockAction = mock(RegisterDomainAction::class);
    $mockAction->shouldReceive('handle')
        ->once()
        ->andReturn([
            'success' => false,
            'message' => 'Domain registration failed: Billing failure; Will exceed credit limit',
        ]);

    $this->app->instance(RegisterDomainAction::class, $mockAction);

    // Process domain registration
    $service = app(DomainRegistrationService::class);
    $contactIds = [
        'registrant' => $this->contact->id,
        'admin' => $this->contact->id,
        'technical' => $this->contact->id,
        'billing' => $this->contact->id,
    ];

    $results = $service->processDomainRegistrations($order, $contactIds);

    // Assert failed registration was recorded
    expect($results['failed'])->toHaveCount(1);
    expect($results['successful'])->toHaveCount(0);

    assertDatabaseHas('failed_domain_registrations', [
        'order_id' => $order->id,
        'order_item_id' => $orderItem->id,
        'domain_name' => 'test.rw',
        'status' => 'pending',
        'retry_count' => 0,
    ]);

    // Assert order status was updated
    $order->refresh();
    expect($order->status)->toBe('requires_attention');

    // Assert retry job was dispatched
    Queue::assertPushed(RetryDomainRegistrationJob::class);

    // Assert user was NOT notified (only admin is notified)
    Notification::assertNothingSentTo($order->user);
});

it('updates order status to completed when retry succeeds', function (): void {
    $order = Order::factory()->create([
        'user_id' => $this->user->id,
        'payment_status' => 'paid',
        'status' => 'requires_attention',
    ]);

    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'domain_name' => 'test.rw',
        'years' => 1,
    ]);

    $failedRegistration = FailedDomainRegistration::query()->create([
        'order_id' => $order->id,
        'order_item_id' => $orderItem->id,
        'domain_name' => 'test.rw',
        'failure_reason' => 'Initial failure',
        'retry_count' => 0,
        'max_retries' => 3,
        'status' => 'pending',
        'contact_ids' => [
            'registrant' => $this->contact->id,
            'admin' => $this->contact->id,
            'technical' => $this->contact->id,
            'billing' => $this->contact->id,
        ],
    ]);

    // Mock successful registration on retry
    $domain = Domain::factory()->create(['owner_id' => $this->user->id]);

    $mockAction = mock(RegisterDomainAction::class);
    $mockAction->shouldReceive('handle')
        ->once()
        ->andReturn([
            'success' => true,
            'domain_id' => $domain->id,
            'message' => 'Domain registered successfully',
        ]);

    $this->app->instance(RegisterDomainAction::class, $mockAction);

    // Execute retry job
    $job = new RetryDomainRegistrationJob($failedRegistration);
    $job->handle($mockAction);

    // Assert failed registration was marked as resolved
    $failedRegistration->refresh();
    expect($failedRegistration->status)->toBe('resolved');
    expect($failedRegistration->resolved_at)->not->toBeNull();

    // Assert order status was updated to completed
    $order->refresh();
    expect($order->status)->toBe('completed');
});

it('marks registration as abandoned after max retries', function (): void {
    $order = Order::factory()->create([
        'user_id' => $this->user->id,
        'payment_status' => 'paid',
        'status' => 'requires_attention',
    ]);

    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'domain_name' => 'test.rw',
        'years' => 1,
    ]);

    $failedRegistration = FailedDomainRegistration::query()->create([
        'order_id' => $order->id,
        'order_item_id' => $orderItem->id,
        'domain_name' => 'test.rw',
        'failure_reason' => 'Initial failure',
        'retry_count' => 2, // Already tried 2 times
        'max_retries' => 3,
        'status' => 'retrying',
        'contact_ids' => [
            'registrant' => $this->contact->id,
            'admin' => $this->contact->id,
            'technical' => $this->contact->id,
            'billing' => $this->contact->id,
        ],
    ]);

    // Mock failed registration on final retry
    $mockAction = mock(RegisterDomainAction::class);
    $mockAction->shouldReceive('handle')
        ->once()
        ->andReturn([
            'success' => false,
            'message' => 'Still failing',
        ]);

    $this->app->instance(RegisterDomainAction::class, $mockAction);

    // Execute retry job
    $job = new RetryDomainRegistrationJob($failedRegistration);
    $job->handle($mockAction);

    // Assert failed registration was marked as abandoned
    $failedRegistration->refresh();
    expect($failedRegistration->status)->toBe('abandoned');
    expect($failedRegistration->retry_count)->toBe(3);
});

it('handles payment success with multiple domains where some fail', function (): void {
    $order = Order::factory()->create([
        'user_id' => $this->user->id,
        'payment_status' => 'paid',
        'status' => 'processing',
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'domain_name' => 'success.rw',
        'years' => 1,
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'domain_name' => 'failed.rw',
        'years' => 1,
    ]);

    // Mock mixed results
    $successfulDomain = Domain::factory()->create(['owner_id' => $this->user->id]);

    $mockAction = mock(RegisterDomainAction::class);
    $mockAction->shouldReceive('handle')
        ->twice()
        ->andReturn(
            ['success' => true, 'domain_id' => $successfulDomain->id, 'message' => 'Success'],
            ['success' => false, 'message' => 'Failed']
        );

    $this->app->instance(RegisterDomainAction::class, $mockAction);

    // Process domain registration
    $service = app(DomainRegistrationService::class);
    $contactIds = [
        'registrant' => $this->contact->id,
        'admin' => $this->contact->id,
        'technical' => $this->contact->id,
        'billing' => $this->contact->id,
    ];

    $results = $service->processDomainRegistrations($order, $contactIds);

    // Assert mixed results
    expect($results['successful'])->toHaveCount(1);
    expect($results['failed'])->toHaveCount(1);

    // Assert order status is partially completed
    $order->refresh();
    expect($order->status)->toBe('partially_completed');

    // Assert only one failed registration was recorded
    assertDatabaseHas('failed_domain_registrations', [
        'order_id' => $order->id,
        'domain_name' => 'failed.rw',
        'status' => 'pending',
    ]);
});

it('maintains payment integrity even when all registrations fail', function (): void {
    $order = Order::factory()->create([
        'user_id' => $this->user->id,
        'payment_status' => 'paid',
        'status' => 'processing',
        'total_amount' => 100.00,
    ]);

    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'domain_name' => 'test.rw',
        'years' => 1,
    ]);

    // Mock failed registration
    $mockAction = mock(RegisterDomainAction::class);
    $mockAction->shouldReceive('handle')
        ->once()
        ->andReturn([
            'success' => false,
            'message' => 'Registration failed',
        ]);

    $this->app->instance(RegisterDomainAction::class, $mockAction);

    // Process domain registration
    $service = app(DomainRegistrationService::class);
    $contactIds = [
        'registrant' => $this->contact->id,
        'admin' => $this->contact->id,
        'technical' => $this->contact->id,
        'billing' => $this->contact->id,
    ];

    $results = $service->processDomainRegistrations($order, $contactIds);

    // Refresh order
    $order->refresh();

    // Assert payment status remains paid (not rolled back)
    expect($order->payment_status)->toBe('paid');

    // Assert order is marked as requiring attention
    expect($order->status)->toBe('requires_attention');

    // Assert payment amount is unchanged
    expect($order->total_amount)->toBe('100.00');
});
