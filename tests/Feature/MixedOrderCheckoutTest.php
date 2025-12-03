<?php

declare(strict_types=1);

use App\Actions\RegisterDomainAction;
use App\Models\Domain;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\DomainRegistrationService;
use App\Services\NotificationService;
use Mockery\MockInterface;

it('only attempts to register domain items, not hosting items', function (): void {
    // Arrange
    $order = Order::factory()->create();
    $domain = Domain::factory()->create();

    // Domain item
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'domain_name' => 'example.com',
        'domain_type' => 'registration',
    ]);

    // Hosting item
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'domain_name' => 'Basic Plan',
        'domain_type' => 'hosting',
    ]);

    // Mock dependencies
    $registerActionMock = $this->mock(RegisterDomainAction::class, function (MockInterface $mock) use ($domain): void {
        // Expect to be called exactly ONCE for 'example.com'
        $mock->shouldReceive('handle')
            ->once()
            ->withArgs(fn ($domainName): bool => $domainName === 'example.com')
            ->andReturn([
                'success' => true,
                'domain_id' => $domain->id,
                'message' => 'Success',
            ]);

        // Should NOT be called for 'Basic Plan'
        $mock->shouldReceive('handle')
            ->withArgs(fn ($domainName): bool => $domainName === 'Basic Plan')
            ->never();
    });

    $notificationService = app(NotificationService::class);

    $service = new DomainRegistrationService(
        $registerActionMock,
        $notificationService
    );

    $contactIds = [
        'registrant' => 1,
        'admin' => 1,
        'tech' => 1,
        'billing' => 1,
    ];

    // Act
    $service->processDomainRegistrations($order, $contactIds);
});
