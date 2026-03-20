<?php

declare(strict_types=1);

use App\Actions\Vps\AssignVpsToSubscriptionAction;
use App\Models\Subscription;
use App\Services\Vps\ContaboService;

it('assigns a VPS instance to a subscription successfully', function (): void {
    $subscription = Subscription::factory()->create(['provider_resource_id' => null]);

    $mock = Mockery::mock(ContaboService::class);
    $mock->shouldReceive('getInstance')
        ->with(12345)
        ->once()
        ->andReturn(['instanceId' => 12345, 'name' => 'vmi12345']);
    app()->instance(ContaboService::class, $mock);

    $result = app(AssignVpsToSubscriptionAction::class)->execute($subscription, 12345);

    expect($result['success'])->toBeTrue();
    expect($subscription->fresh()->provider_resource_id)->toBe('12345');
});

it('prevents duplicate instance assignment', function (): void {
    $existingSubscription = Subscription::factory()->create(['provider_resource_id' => '12345']);
    $newSubscription = Subscription::factory()->create(['provider_resource_id' => null]);

    $mock = Mockery::mock(ContaboService::class);
    app()->instance(ContaboService::class, $mock);

    $result = app(AssignVpsToSubscriptionAction::class)->execute($newSubscription, 12345);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('already assigned');
    expect($newSubscription->fresh()->provider_resource_id)->toBeNull();
});

it('handles API failure when verifying instance', function (): void {
    $subscription = Subscription::factory()->create(['provider_resource_id' => null]);

    $mock = Mockery::mock(ContaboService::class);
    $mock->shouldReceive('getInstance')
        ->with(99999)
        ->once()
        ->andThrow(new RuntimeException('Instance not found'));
    app()->instance(ContaboService::class, $mock);

    $result = app(AssignVpsToSubscriptionAction::class)->execute($subscription, 99999);

    expect($result['success'])->toBeFalse();
    expect($subscription->fresh()->provider_resource_id)->toBeNull();
});
