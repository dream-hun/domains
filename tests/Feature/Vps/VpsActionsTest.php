<?php

declare(strict_types=1);

use App\Actions\Vps\CancelVpsAction;
use App\Actions\Vps\ChangeVpsDisplayNameAction;
use App\Actions\Vps\ExtendVpsStorageAction;
use App\Actions\Vps\MoveVpsRegionAction;
use App\Actions\Vps\OrderVpsLicenseAction;
use App\Actions\Vps\ReinstallVpsAction;
use App\Actions\Vps\RescueVpsAction;
use App\Actions\Vps\ResetVpsCredentialsAction;
use App\Actions\Vps\RestartVpsAction;
use App\Actions\Vps\ShutdownVpsAction;
use App\Actions\Vps\UpgradeVpsAction;
use App\Models\Subscription;
use App\Services\Vps\ContaboService;

it('restarts a VPS instance successfully', function (): void {
    $subscription = Subscription::factory()->create(['provider_resource_id' => '12345']);

    $mock = Mockery::mock(ContaboService::class);
    $mock->shouldReceive('restartInstance')->with(12345)->once()->andReturn([]);
    app()->instance(ContaboService::class, $mock);

    $result = resolve(RestartVpsAction::class)->execute($subscription);

    expect($result['success'])->toBeTrue();
    expect($result['message'])->toContain('restarting');
});

it('handles restart failure gracefully', function (): void {
    $subscription = Subscription::factory()->create(['provider_resource_id' => '12345']);

    $mock = Mockery::mock(ContaboService::class);
    $mock->shouldReceive('restartInstance')->with(12345)->once()->andThrow(new RuntimeException('API Error'));
    app()->instance(ContaboService::class, $mock);

    $result = resolve(RestartVpsAction::class)->execute($subscription);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('Failed');
});

it('shuts down a VPS instance successfully', function (): void {
    $subscription = Subscription::factory()->create(['provider_resource_id' => '12345']);

    $mock = Mockery::mock(ContaboService::class);
    $mock->shouldReceive('shutdownInstance')->with(12345)->once()->andReturn([]);
    app()->instance(ContaboService::class, $mock);

    $result = resolve(ShutdownVpsAction::class)->execute($subscription);

    expect($result['success'])->toBeTrue();
    expect($result['message'])->toContain('shutting down');
});

it('handles shutdown VPS instance failure gracefully', function (): void {
    $subscription = Subscription::factory()->create(['provider_resource_id' => '12345']);

    $mock = Mockery::mock(ContaboService::class);
    $mock->shouldReceive('shutdownInstance')->with(12345)->once()->andThrow(new RuntimeException('API Error'));
    app()->instance(ContaboService::class, $mock);

    $result = resolve(ShutdownVpsAction::class)->execute($subscription);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('Failed');
});

it('changes VPS display name successfully', function (): void {
    $subscription = Subscription::factory()->create(['provider_resource_id' => '12345']);

    $mock = Mockery::mock(ContaboService::class);
    $mock->shouldReceive('updateInstance')->with(12345, 'New Name')->once()->andReturn([]);
    app()->instance(ContaboService::class, $mock);

    $result = resolve(ChangeVpsDisplayNameAction::class)->execute($subscription, 'New Name');

    expect($result['success'])->toBeTrue();
    expect($result['message'])->toContain('Display name updated');
});

it('handles change VPS display name failure gracefully', function (): void {
    $subscription = Subscription::factory()->create(['provider_resource_id' => '12345']);

    $mock = Mockery::mock(ContaboService::class);
    $mock->shouldReceive('updateInstance')->with(12345, 'New Name')->once()->andThrow(new RuntimeException('API Error'));
    app()->instance(ContaboService::class, $mock);

    $result = resolve(ChangeVpsDisplayNameAction::class)->execute($subscription, 'New Name');

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('Failed');
});

it('cancels a VPS instance successfully', function (): void {
    $subscription = Subscription::factory()->create(['provider_resource_id' => '12345']);

    $mock = Mockery::mock(ContaboService::class);
    $mock->shouldReceive('cancelInstance')->with(12345, null)->once()->andReturn([]);
    app()->instance(ContaboService::class, $mock);

    $result = resolve(CancelVpsAction::class)->execute($subscription);

    expect($result['success'])->toBeTrue();
    expect($result['message'])->toContain('cancellation');
});

it('handles cancel VPS instance failure gracefully', function (): void {
    $subscription = Subscription::factory()->create(['provider_resource_id' => '12345']);

    $mock = Mockery::mock(ContaboService::class);
    $mock->shouldReceive('cancelInstance')->with(12345, null)->once()->andThrow(new RuntimeException('API Error'));
    app()->instance(ContaboService::class, $mock);

    $result = resolve(CancelVpsAction::class)->execute($subscription);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('Failed');
});

it('reinstalls a VPS instance successfully', function (): void {
    $subscription = Subscription::factory()->create(['provider_resource_id' => '12345']);

    $mock = Mockery::mock(ContaboService::class);
    $mock->shouldReceive('reinstallInstance')->with(12345, ['imageId' => 'ubuntu-22.04'])->once()->andReturn([]);
    app()->instance(ContaboService::class, $mock);

    $result = resolve(ReinstallVpsAction::class)->execute($subscription, ['imageId' => 'ubuntu-22.04']);

    expect($result['success'])->toBeTrue();
    expect($result['message'])->toContain('reinstalled');
});

it('handles reinstall failure gracefully', function (): void {
    $subscription = Subscription::factory()->create(['provider_resource_id' => '12345']);

    $mock = Mockery::mock(ContaboService::class);
    $mock->shouldReceive('reinstallInstance')->with(12345, ['imageId' => 'bad-image'])->once()->andThrow(new RuntimeException('Invalid image'));
    app()->instance(ContaboService::class, $mock);

    $result = resolve(ReinstallVpsAction::class)->execute($subscription, ['imageId' => 'bad-image']);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('Failed');
});

it('upgrades a VPS instance successfully', function (): void {
    $subscription = Subscription::factory()->create(['provider_resource_id' => '12345']);

    $mock = Mockery::mock(ContaboService::class);
    $mock->shouldReceive('upgradeInstance')->with(12345, [])->once()->andReturn([]);
    app()->instance(ContaboService::class, $mock);

    $result = resolve(UpgradeVpsAction::class)->execute($subscription, []);

    expect($result['success'])->toBeTrue();
    expect($result['message'])->toContain('upgrade');
});

it('handles upgrade failure gracefully', function (): void {
    $subscription = Subscription::factory()->create(['provider_resource_id' => '12345']);

    $mock = Mockery::mock(ContaboService::class);
    $mock->shouldReceive('upgradeInstance')->with(12345, [])->once()->andThrow(new RuntimeException('API Error'));
    app()->instance(ContaboService::class, $mock);

    $result = resolve(UpgradeVpsAction::class)->execute($subscription, []);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('Failed');
});

it('orders a VPS license successfully', function (): void {
    $subscription = Subscription::factory()->create(['provider_resource_id' => '12345']);

    $mock = Mockery::mock(ContaboService::class);
    $mock->shouldReceive('upgradeInstance')->with(12345, ['license' => 'cPanel'])->once()->andReturn([]);
    app()->instance(ContaboService::class, $mock);

    $result = resolve(OrderVpsLicenseAction::class)->execute($subscription, 'cPanel');

    expect($result['success'])->toBeTrue();
    expect($result['message'])->toContain('cPanel');
});

it('handles order license failure gracefully', function (): void {
    $subscription = Subscription::factory()->create(['provider_resource_id' => '12345']);

    $mock = Mockery::mock(ContaboService::class);
    $mock->shouldReceive('upgradeInstance')->with(12345, ['license' => 'cPanel'])->once()->andThrow(new RuntimeException('API Error'));
    app()->instance(ContaboService::class, $mock);

    $result = resolve(OrderVpsLicenseAction::class)->execute($subscription, 'cPanel');

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('Failed');
});

it('extends VPS storage successfully', function (): void {
    $subscription = Subscription::factory()->create(['provider_resource_id' => '12345']);

    $mock = Mockery::mock(ContaboService::class);
    $mock->shouldReceive('upgradeInstance')->with(12345, ['extraStorage' => 100])->once()->andReturn([]);
    app()->instance(ContaboService::class, $mock);

    $result = resolve(ExtendVpsStorageAction::class)->execute($subscription, 100);

    expect($result['success'])->toBeTrue();
    expect($result['message'])->toContain('100 GB');
});

it('handles extend storage failure gracefully', function (): void {
    $subscription = Subscription::factory()->create(['provider_resource_id' => '12345']);

    $mock = Mockery::mock(ContaboService::class);
    $mock->shouldReceive('upgradeInstance')->with(12345, ['extraStorage' => 100])->once()->andThrow(new RuntimeException('API Error'));
    app()->instance(ContaboService::class, $mock);

    $result = resolve(ExtendVpsStorageAction::class)->execute($subscription, 100);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('Failed');
});

it('initiates VPS region migration successfully', function (): void {
    $subscription = Subscription::factory()->create(['provider_resource_id' => '12345']);

    $mock = Mockery::mock(ContaboService::class);
    $mock->shouldReceive('createSnapshot')->once()->andReturn(['snapshotId' => 'snap-123']);
    app()->instance(ContaboService::class, $mock);

    $result = resolve(MoveVpsRegionAction::class)->execute($subscription, 'EU');

    expect($result['success'])->toBeTrue();
    expect($result['message'])->toContain('snapshot created');
    expect($result['message'])->toContain('EU');
});

it('handles region migration failure gracefully', function (): void {
    $subscription = Subscription::factory()->create(['provider_resource_id' => '12345']);

    $mock = Mockery::mock(ContaboService::class);
    $mock->shouldReceive('createSnapshot')->once()->andThrow(new RuntimeException('API Error'));
    app()->instance(ContaboService::class, $mock);

    $result = resolve(MoveVpsRegionAction::class)->execute($subscription, 'EU');

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('Failed');
});

it('rescues a VPS instance successfully', function (): void {
    $subscription = Subscription::factory()->create(['provider_resource_id' => '12345']);

    $payload = ['rootPassword' => 123456];

    $mock = Mockery::mock(ContaboService::class);
    $mock->shouldReceive('rescueInstance')->with(12345, $payload)->once()->andReturn([]);
    app()->instance(ContaboService::class, $mock);

    $result = resolve(RescueVpsAction::class)->execute($subscription, $payload);

    expect($result['success'])->toBeTrue();
    expect($result['message'])->toContain('rescue mode');
});

it('handles rescue VPS instance failure gracefully', function (): void {
    $subscription = Subscription::factory()->create(['provider_resource_id' => '12345']);

    $payload = ['rootPassword' => 123456];

    $mock = Mockery::mock(ContaboService::class);
    $mock->shouldReceive('rescueInstance')->with(12345, $payload)->once()->andThrow(new RuntimeException('API Error'));
    app()->instance(ContaboService::class, $mock);

    $result = resolve(RescueVpsAction::class)->execute($subscription, $payload);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('Failed');
});

it('resets VPS credentials successfully', function (): void {
    $subscription = Subscription::factory()->create(['provider_resource_id' => '12345']);

    $payload = ['sshKeys' => [1, 2, 3]];

    $mock = Mockery::mock(ContaboService::class);
    $mock->shouldReceive('resetInstancePassword')->with(12345, $payload)->once()->andReturn([]);
    app()->instance(ContaboService::class, $mock);

    $result = resolve(ResetVpsCredentialsAction::class)->execute($subscription, $payload);

    expect($result['success'])->toBeTrue();
    expect($result['message'])->toContain('credentials have been reset');
});

it('handles reset VPS credentials failure gracefully', function (): void {
    $subscription = Subscription::factory()->create(['provider_resource_id' => '12345']);

    $payload = ['sshKeys' => [1, 2, 3]];

    $mock = Mockery::mock(ContaboService::class);
    $mock->shouldReceive('resetInstancePassword')->with(12345, $payload)->once()->andThrow(new RuntimeException('API Error'));
    app()->instance(ContaboService::class, $mock);

    $result = resolve(ResetVpsCredentialsAction::class)->execute($subscription, $payload);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('Failed');
});
