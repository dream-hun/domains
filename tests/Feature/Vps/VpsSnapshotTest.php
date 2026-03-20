<?php

declare(strict_types=1);

use App\Actions\Vps\CreateVpsSnapshotAction;
use App\Actions\Vps\DeleteVpsSnapshotAction;
use App\Actions\Vps\RestoreVpsSnapshotAction;
use App\Models\Subscription;
use App\Services\Vps\ContaboService;

it('creates a VPS snapshot successfully', function (): void {
    $subscription = Subscription::factory()->create(['provider_resource_id' => '12345']);

    $mock = Mockery::mock(ContaboService::class);
    $mock->shouldReceive('createSnapshot')
        ->with(12345, 'test-snapshot', 'Test description')
        ->once()
        ->andReturn(['snapshotId' => 'snap-123']);
    app()->instance(ContaboService::class, $mock);

    $result = app(CreateVpsSnapshotAction::class)->execute($subscription, 'test-snapshot', 'Test description');

    expect($result['success'])->toBeTrue();
    expect($result['message'])->toContain('Snapshot created');
});

it('deletes a VPS snapshot successfully', function (): void {
    $subscription = Subscription::factory()->create(['provider_resource_id' => '12345']);

    $mock = Mockery::mock(ContaboService::class);
    $mock->shouldReceive('deleteSnapshot')
        ->with(12345, 'snap-123')
        ->once()
        ->andReturn(true);
    app()->instance(ContaboService::class, $mock);

    $result = app(DeleteVpsSnapshotAction::class)->execute($subscription, 'snap-123');

    expect($result['success'])->toBeTrue();
    expect($result['message'])->toContain('Snapshot deleted');
});

it('handles delete VPS snapshot failure gracefully', function (): void {
    $subscription = Subscription::factory()->create(['provider_resource_id' => '12345']);

    $mock = Mockery::mock(ContaboService::class);
    $mock->shouldReceive('deleteSnapshot')
        ->with(12345, 'snap-123')
        ->once()
        ->andThrow(new RuntimeException('API Error'));
    app()->instance(ContaboService::class, $mock);

    $result = app(DeleteVpsSnapshotAction::class)->execute($subscription, 'snap-123');

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('Failed');
});

it('restores a VPS snapshot successfully', function (): void {
    $subscription = Subscription::factory()->create(['provider_resource_id' => '12345']);

    $mock = Mockery::mock(ContaboService::class);
    $mock->shouldReceive('revertSnapshot')
        ->with(12345, 'snap-123')
        ->once()
        ->andReturn([]);
    app()->instance(ContaboService::class, $mock);

    $result = app(RestoreVpsSnapshotAction::class)->execute($subscription, 'snap-123');

    expect($result['success'])->toBeTrue();
    expect($result['message'])->toContain('restored');
});

it('handles restore VPS snapshot failure gracefully', function (): void {
    $subscription = Subscription::factory()->create(['provider_resource_id' => '12345']);

    $mock = Mockery::mock(ContaboService::class);
    $mock->shouldReceive('revertSnapshot')
        ->with(12345, 'snap-123')
        ->once()
        ->andThrow(new RuntimeException('API Error'));
    app()->instance(ContaboService::class, $mock);

    $result = app(RestoreVpsSnapshotAction::class)->execute($subscription, 'snap-123');

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('Failed');
});

it('handles snapshot creation failure', function (): void {
    $subscription = Subscription::factory()->create(['provider_resource_id' => '12345']);

    $mock = Mockery::mock(ContaboService::class);
    $mock->shouldReceive('createSnapshot')
        ->once()
        ->andThrow(new RuntimeException('Snapshot limit reached'));
    app()->instance(ContaboService::class, $mock);

    $result = app(CreateVpsSnapshotAction::class)->execute($subscription, 'test-snapshot');

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('Failed');
});
