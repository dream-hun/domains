<?php

declare(strict_types=1);

use App\Http\Middleware\AuthGates;
use App\Models\HostingCategory;
use App\Models\HostingPlan;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Vps\ContaboService;
use Illuminate\Http\Request;

function createVpsControllerUser(array $permissions = ['vps_access']): User
{
    $role = Role::query()->firstOrCreate(['id' => 1], ['title' => 'Admin']);

    foreach ($permissions as $permissionTitle) {
        $permission = Permission::query()->where('title', $permissionTitle)->first()
            ?? Permission::query()->create(['title' => $permissionTitle]);

        if (! $role->permissions()->where('permissions.id', $permission->id)->exists()) {
            $role->permissions()->attach($permission->id);
        }
    }

    $user = User::factory()->create();
    $user->roles()->attach($role);

    return $user;
}

function createNonAdminVpsUser(array $permissions = ['vps_access']): User
{
    $role = Role::query()->firstOrCreate(['id' => 99], ['title' => 'Staff']);

    foreach ($permissions as $permissionTitle) {
        $permission = Permission::query()->where('title', $permissionTitle)->first()
            ?? Permission::query()->create(['title' => $permissionTitle]);

        if (! $role->permissions()->where('permissions.id', $permission->id)->exists()) {
            $role->permissions()->attach($permission->id);
        }
    }

    $user = User::factory()->create();
    $user->roles()->attach($role);

    return $user;
}

function setupVpsControllerGates(User $user): void
{
    auth()->login($user);
    (new AuthGates)->handle(Request::create('/'), fn ($req) => $req);
}

function makeVpsControllerApiInstance(array $overrides = []): array
{
    return array_merge([
        'instanceId' => 12345,
        'name' => 'vmi123456',
        'displayName' => 'My VPS',
        'status' => 'running',
        'productType' => 'V45',
        'defaultUser' => 'root',
        'region' => 'EU',
        'dataCenter' => 'European Union (Germany)',
        'ipConfig' => [
            'v4' => ['ip' => '192.168.1.1'],
            'v6' => ['ip' => '::1'],
        ],
        'osType' => 'Linux',
        'cpuCores' => 4,
        'ramMb' => 8192,
        'diskMb' => 204800,
        'imageId' => 'ubuntu-22.04',
        'createdDate' => '2025-01-01T00:00:00.000Z',
        'addOns' => ['maxSnapshots' => 5],
    ], $overrides);
}

it('admin vps index maps instances (and ignores missing API instances)', function (): void {
    $user = createVpsControllerUser(['vps_access']);

    $subMissingApi = Subscription::factory()->create([
        'provider_resource_id' => '11111',
    ]);
    $subMatchingApi = Subscription::factory()->create([
        'provider_resource_id' => '12345',
    ]);

    $mock = Mockery::mock(ContaboService::class);
    $mock->shouldReceive('listInstances')->once()->andReturn([
        'data' => [
            makeVpsControllerApiInstance(['instanceId' => 12345, 'displayName' => 'Matched VPS']),
        ],
    ]);
    app()->instance(ContaboService::class, $mock);

    setupVpsControllerGates($user);

    $response = $this->actingAs($user)
        ->get(route('admin.vps.index'));

    $response->assertSuccessful();

    $instances = $response->viewData('instances');
    expect($instances)->toHaveCount(1);
    expect($instances[0]['subscription_uuid'])->toBe($subMatchingApi->uuid);
});

it('admin vps index handles RuntimeException from provider listInstances', function (): void {
    $user = createVpsControllerUser(['vps_access']);

    Subscription::factory()->create([
        'provider_resource_id' => '12345',
    ]);

    $mock = Mockery::mock(ContaboService::class);
    $mock->shouldReceive('listInstances')->once()->andThrow(new RuntimeException('Provider down'));
    app()->instance(ContaboService::class, $mock);

    setupVpsControllerGates($user);

    $response = $this->actingAs($user)
        ->get(route('admin.vps.index'));

    $response->assertSuccessful();
    $response->assertViewHas('errorMessage', 'Failed to load VPS instances. Please try again.');
});

it('admin vps show returns early when provider_resource_id is missing', function (): void {
    $user = createVpsControllerUser(['vps_show']);

    $subscription = Subscription::factory()->create([
        'provider_resource_id' => null,
    ]);

    $mock = Mockery::mock(ContaboService::class);
    $mock->shouldReceive('getInstance')->never();
    app()->instance(ContaboService::class, $mock);

    setupVpsControllerGates($user);

    $response = $this->actingAs($user)
        ->get(route('admin.vps.show', $subscription));

    $response->assertSuccessful();
    $response->assertViewHas('errorMessage', 'No VPS instance linked to this subscription yet. It will be assigned shortly.');
});

it('admin vps show handles RuntimeException from provider getInstance', function (): void {
    $user = createVpsControllerUser(['vps_show']);

    $subscription = Subscription::factory()->create([
        'provider_resource_id' => '12345',
    ]);

    $mock = Mockery::mock(ContaboService::class);
    $mock->shouldReceive('getInstance')->once()->andThrow(new RuntimeException('API Error'));
    app()->instance(ContaboService::class, $mock);

    setupVpsControllerGates($user);

    $response = $this->actingAs($user)
        ->get(route('admin.vps.show', $subscription));

    $response->assertSuccessful();
    $response->assertViewHas('errorMessage', 'Failed to load VPS instance details.');
});

it('admin vps show loads instance detail with backups when vps_backup_access is granted', function (): void {
    $user = createVpsControllerUser(['vps_show', 'vps_backup_access']);

    $subscription = Subscription::factory()->create([
        'provider_resource_id' => '12345',
    ]);

    $backup = [
        'backupId' => 'bkp-999',
        'name' => 'Weekly Backup',
        'sizeMb' => 20480,
        'createdDate' => '2025-07-01T00:00:00.000Z',
    ];

    $mock = Mockery::mock(ContaboService::class);
    $mock->shouldReceive('getInstance')
        ->with(12345)
        ->once()
        ->andReturn(makeVpsControllerApiInstance(['instanceId' => 12345]));
    $mock->shouldReceive('listInstanceBackups')
        ->with(12345)
        ->once()
        ->andReturn(['data' => [$backup]]);
    app()->instance(ContaboService::class, $mock);

    setupVpsControllerGates($user);

    $response = $this->actingAs($user)->get(route('admin.vps.show', $subscription));

    $response->assertSuccessful();
    $response->assertViewHas('backups', [$backup]);
    $response->assertSee('Automated Backups');
    $response->assertSee('bkp-999');
    $response->assertSee('Weekly Backup');
    $response->assertSee('20 GB');
});

it('admin vps assign renders unassigned lists', function (): void {
    $user = createVpsControllerUser(['vps_assign']);

    $unassignedSubscription = Subscription::factory()->create([
        'provider_resource_id' => null,
    ]);
    $assignedSubscription = Subscription::factory()->create([
        'provider_resource_id' => '22222',
    ]);

    $mock = Mockery::mock(ContaboService::class);
    $mock->shouldReceive('listAllInstances')->once()->andReturn([
        makeVpsControllerApiInstance(['instanceId' => 11111, 'displayName' => 'Unassigned VPS']),
        makeVpsControllerApiInstance(['instanceId' => 22222, 'displayName' => 'Assigned VPS']),
    ]);
    app()->instance(ContaboService::class, $mock);

    setupVpsControllerGates($user);

    $response = $this->actingAs($user)
        ->get(route('admin.vps.assign'));

    $response->assertSuccessful();

    $response->assertViewHas('unassignedSubscriptions');
    $response->assertViewHas('unassignedInstances');

    $unassignedSubscriptions = $response->viewData('unassignedSubscriptions');
    $unassignedInstances = $response->viewData('unassignedInstances');

    // All subscriptions are now returned (assigned and unassigned) to allow re-assignment
    expect($unassignedSubscriptions)->toHaveCount(2);
    $subIds = array_column($unassignedSubscriptions, 'id');
    expect($subIds)->toContain($unassignedSubscription->id);
    expect($subIds)->toContain($assignedSubscription->id);

    // Verify is_assigned flag is set correctly
    $unassignedSubEntry = collect($unassignedSubscriptions)->firstWhere('id', $unassignedSubscription->id);
    $assignedSubEntry = collect($unassignedSubscriptions)->firstWhere('id', $assignedSubscription->id);
    expect($unassignedSubEntry['is_assigned'])->toBeFalse();
    expect($assignedSubEntry['is_assigned'])->toBeTrue();

    // All instances are now returned to allow re-assignment
    expect($unassignedInstances)->toHaveCount(2);
    $instanceIds = array_column($unassignedInstances, 'instanceId');
    expect($instanceIds)->toContain(11111);
    expect($instanceIds)->toContain(22222);

    $unassignedInstEntry = collect($unassignedInstances)->firstWhere('instanceId', 11111);
    $assignedInstEntry = collect($unassignedInstances)->firstWhere('instanceId', 22222);
    expect($unassignedInstEntry['is_assigned'])->toBeFalse();
    expect($assignedInstEntry['is_assigned'])->toBeTrue();
});

it('admin vps assign handles RuntimeException from provider listInstances', function (): void {
    $user = createVpsControllerUser(['vps_assign']);

    $mock = Mockery::mock(ContaboService::class);
    $mock->shouldReceive('listAllInstances')->once()->andThrow(new RuntimeException('Provider down'));
    app()->instance(ContaboService::class, $mock);

    setupVpsControllerGates($user);

    $response = $this->actingAs($user)
        ->get(route('admin.vps.assign'));

    $response->assertSuccessful();
    $response->assertViewHas('errorMessage', 'Failed to load data. Please try again.');
});

it('admin vps assign store assigns an instance to a subscription', function (): void {
    $user = createVpsControllerUser(['vps_assign']);

    $subscription = Subscription::factory()->create([
        'provider_resource_id' => null,
    ]);

    $mock = Mockery::mock(ContaboService::class);
    $mock->shouldReceive('getInstance')
        ->with(12345)
        ->once()
        ->andReturn(makeVpsControllerApiInstance(['instanceId' => 12345]));
    app()->instance(ContaboService::class, $mock);

    setupVpsControllerGates($user);

    $response = $this->actingAs($user)
        ->from(route('admin.vps.assign'))
        ->post(route('admin.vps.assign.store'), [
            'subscription_id' => $subscription->id,
            'instance_id' => 12345,
        ]);

    $response->assertRedirect(route('admin.vps.index'));
    $response->assertSessionHas('success');

    $subscription->refresh();
    expect($subscription->provider_resource_id)->toBe('12345');
});

it('admin vps assign store validates required instance_id', function (): void {
    $user = createVpsControllerUser(['vps_assign']);

    $subscription = Subscription::factory()->create([
        'provider_resource_id' => null,
    ]);

    setupVpsControllerGates($user);

    $response = $this->actingAs($user)
        ->from(route('admin.vps.assign'))
        ->post(route('admin.vps.assign.store'), [
            'subscription_id' => $subscription->id,
        ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors('instance_id');
});

it('admin vps action endpoints succeed', function (): void {
    $user = createVpsControllerUser([
        'vps_access',
        'vps_restart',
        'vps_shutdown',
        'vps_reinstall',
        'vps_rescue',
        'vps_reset_credentials',
        'vps_change_display_name',
        'vps_snapshot_create',
        'vps_snapshot_delete',
        'vps_backup_restore',
        'vps_upgrade',
        'vps_order_license',
        'vps_extend_storage',
        'vps_move_region',
        'vps_cancel',
    ]);

    $category = HostingCategory::factory()->create();
    $currentPlan = HostingPlan::factory()->create([
        'category_id' => $category->id,
        'sort_order' => 1,
    ]);
    $nextPlan = HostingPlan::factory()->create([
        'category_id' => $category->id,
        'sort_order' => 2,
        'contabo_product_id' => 'V99',
    ]);

    $subscription = Subscription::factory()->create([
        'provider_resource_id' => '12345',
        'hosting_plan_id' => $currentPlan->id,
    ]);

    $mock = Mockery::mock(ContaboService::class);

    $mock->shouldReceive('restartInstance')->with(12345)->once()->andReturn([]);
    $mock->shouldReceive('shutdownInstance')->with(12345)->once()->andReturn([]);
    $mock->shouldReceive('reinstallInstance')->with(12345, ['imageId' => 'ubuntu-22.04'])->once()->andReturn([]);
    $mock->shouldReceive('createSecret')
        ->withArgs(fn (array $payload): bool => $payload['type'] === 'password')
        ->twice()
        ->andReturn(['secretId' => 99999]);
    $mock->shouldReceive('rescueInstance')->with(12345, ['rootPassword' => 99999])->once()->andReturn([]);
    $mock->shouldReceive('resetInstancePassword')->with(12345, ['rootPassword' => 99999])->once()->andReturn([]);
    $mock->shouldReceive('updateInstance')->with(12345, 'New Name')->once()->andReturn([]);

    $mock->shouldReceive('createSnapshot')
        ->with(12345, 'test-snapshot', '')
        ->once()
        ->andReturn(['snapshotId' => 'snap-123']);

    $mock->shouldReceive('createSnapshot')
        ->withArgs(fn (int $instanceId, string $snapshotName, string $description): bool => $instanceId === 12345
            && str_contains($snapshotName, 'region-migration-EU-')
            && $description === 'Pre-migration snapshot for region move to EU')
        ->once()
        ->andReturn(['snapshotId' => 'snap-999']);

    $mock->shouldReceive('deleteSnapshot')->with(12345, 'snap-123')->once()->andReturn(true);
    $mock->shouldReceive('revertSnapshot')->with(12345, 'snap-123')->once()->andReturn([]);

    $mock->shouldReceive('upgradeInstance')->with(12345, ['productId' => 'V99'])->once()->andReturn([]);
    $mock->shouldReceive('upgradeInstance')->with(12345, ['license' => 'cPanel'])->once()->andReturn([]);
    $mock->shouldReceive('upgradeInstance')->with(12345, ['extraStorage' => 100])->once()->andReturn([]);

    $mock->shouldReceive('cancelInstance')->with(12345, null)->once()->andReturn([]);

    app()->instance(ContaboService::class, $mock);
    setupVpsControllerGates($user);

    $subscriptionUuid = $subscription->uuid;

    $this->actingAs($user)
        ->from(route('admin.vps.show', $subscription))
        ->post(route('admin.vps.restart', $subscription))
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->actingAs($user)
        ->from(route('admin.vps.show', $subscription))
        ->post(route('admin.vps.shutdown', $subscription))
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->actingAs($user)
        ->from(route('admin.vps.show', $subscription))
        ->post(route('admin.vps.reinstall', $subscription), [
            'imageId' => 'ubuntu-22.04',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->actingAs($user)
        ->from(route('admin.vps.show', $subscription))
        ->post(route('admin.vps.rescue', $subscription))
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->actingAs($user)
        ->from(route('admin.vps.show', $subscription))
        ->post(route('admin.vps.reset-credentials', $subscription))
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->actingAs($user)
        ->from(route('admin.vps.show', $subscription))
        ->post(route('admin.vps.display-name', $subscription), [
            'display_name' => 'New Name',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->actingAs($user)
        ->from(route('admin.vps.show', $subscription))
        ->post(route('admin.vps.snapshots.store', $subscription), [
            'name' => 'test-snapshot',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->actingAs($user)
        ->from(route('admin.vps.show', $subscription))
        ->delete(route('admin.vps.snapshots.destroy', [$subscription, 'snap-123']))
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->actingAs($user)
        ->from(route('admin.vps.show', $subscription))
        ->post(route('admin.vps.snapshots.restore', [$subscription, 'snap-123']))
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->actingAs($user)
        ->from(route('admin.vps.show', $subscription))
        ->post(route('admin.vps.upgrade', $subscription))
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->actingAs($user)
        ->from(route('admin.vps.show', $subscription))
        ->post(route('admin.vps.order-license', $subscription), [
            'license_type' => 'cPanel',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->actingAs($user)
        ->from(route('admin.vps.show', $subscription))
        ->post(route('admin.vps.extend-storage', $subscription), [
            'storage_gb' => 100,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->actingAs($user)
        ->from(route('admin.vps.show', $subscription))
        ->post(route('admin.vps.move-region', $subscription), [
            'target_region' => 'EU',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->actingAs($user)
        ->from(route('admin.vps.show', $subscription))
        ->post(route('admin.vps.cancel', $subscription))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($subscriptionUuid)->not->toBeEmpty();
});

it('admin vps action endpoints validate ChangeVpsDisplayNameRequest', function (): void {
    $user = createVpsControllerUser(['vps_change_display_name']);

    $subscription = Subscription::factory()->create(['provider_resource_id' => '12345']);

    setupVpsControllerGates($user);

    $response = $this->actingAs($user)
        ->from(route('admin.vps.show', $subscription))
        ->post(route('admin.vps.display-name', $subscription), [
            // display_name is missing
        ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors('display_name');
});

it('admin vps action endpoints validate CreateVpsSnapshotRequest', function (): void {
    $user = createVpsControllerUser(['vps_snapshot_create']);

    $subscription = Subscription::factory()->create(['provider_resource_id' => '12345']);

    setupVpsControllerGates($user);

    $response = $this->actingAs($user)
        ->from(route('admin.vps.show', $subscription))
        ->post(route('admin.vps.snapshots.store', $subscription), [
            // name is missing
            'description' => 'Test',
        ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors('name');
});

it('admin vps action endpoints validate OrderVpsLicenseRequest', function (): void {
    $user = createVpsControllerUser(['vps_order_license']);

    $subscription = Subscription::factory()->create(['provider_resource_id' => '12345']);

    setupVpsControllerGates($user);

    $response = $this->actingAs($user)
        ->from(route('admin.vps.show', $subscription))
        ->post(route('admin.vps.order-license', $subscription), [
            'license_type' => 'Invalid',
        ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors('license_type');
});

it('admin vps action endpoints validate ExtendVpsStorageRequest', function (): void {
    $user = createVpsControllerUser(['vps_extend_storage']);

    $subscription = Subscription::factory()->create(['provider_resource_id' => '12345']);

    setupVpsControllerGates($user);

    $response = $this->actingAs($user)
        ->from(route('admin.vps.show', $subscription))
        ->post(route('admin.vps.extend-storage', $subscription), [
            'storage_gb' => 0,
        ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors('storage_gb');
});

it('admin vps action endpoints validate MoveVpsRegionRequest', function (): void {
    $user = createVpsControllerUser(['vps_move_region']);

    $subscription = Subscription::factory()->create(['provider_resource_id' => '12345']);

    setupVpsControllerGates($user);

    $response = $this->actingAs($user)
        ->from(route('admin.vps.show', $subscription))
        ->post(route('admin.vps.move-region', $subscription), [
            // too long (>50 chars)
            'target_region' => str_repeat('A', 51),
        ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors('target_region');
});

it('user vps index handles RuntimeException from provider listAllInstances', function (): void {
    $user = createVpsControllerUser(['vps_access']);

    $mock = Mockery::mock(ContaboService::class);
    $mock->shouldReceive('listAllInstances')->once()->andThrow(new RuntimeException('Provider down'));
    app()->instance(ContaboService::class, $mock);

    setupVpsControllerGates($user);

    // Ensure there is at least one subscription so the code reaches listAllInstances call
    Subscription::factory()->create([
        'user_id' => $user->id,
        'provider_resource_id' => '12345',
    ]);

    $response = $this->actingAs($user)
        ->get(route('user.vps.index'));

    $response->assertSuccessful();
    $response->assertViewHas('errorMessage', 'Failed to load VPS instances. Please try again.');
});

it('user vps index shows mapped instance data for current user subscriptions', function (): void {
    $user = createVpsControllerUser(['vps_access']);

    Subscription::factory()->create([
        'user_id' => $user->id,
        'provider_resource_id' => '12345',
    ]);

    $mock = Mockery::mock(ContaboService::class);
    $mock->shouldReceive('listAllInstances')->once()->andReturn([
        makeVpsControllerApiInstance([
            'instanceId' => 12345,
            'displayName' => 'User VPS',
        ]),
    ]);
    app()->instance(ContaboService::class, $mock);

    setupVpsControllerGates($user);

    $response = $this->actingAs($user)
        ->get(route('user.vps.index'));

    $response->assertSuccessful();
    $response->assertSee('User VPS');

    $instances = $response->viewData('instances');
    expect($instances)->toHaveCount(1);
    expect($instances[0]['instance_id'])->toBe(12345);
    expect($instances[0]['status'])->toBe('running');
});

it('user vps index filters out subscriptions missing from provider response', function (): void {
    $user = createVpsControllerUser(['vps_access']);

    Subscription::factory()->create([
        'user_id' => $user->id,
        'provider_resource_id' => '12345',
    ]);

    $mock = Mockery::mock(ContaboService::class);
    $mock->shouldReceive('listAllInstances')->once()->andReturn([
        makeVpsControllerApiInstance([
            'instanceId' => 99999,
            'displayName' => 'Other VPS',
        ]),
    ]);
    app()->instance(ContaboService::class, $mock);

    setupVpsControllerGates($user);

    $response = $this->actingAs($user)
        ->get(route('user.vps.index'));

    $response->assertSuccessful();

    $instances = $response->viewData('instances');
    expect($instances)->toBeArray()->toHaveCount(0);
});

it('user vps show returns early when provider_resource_id is missing', function (): void {
    $user = createVpsControllerUser(['vps_show']);

    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'provider_resource_id' => null,
    ]);

    $mock = Mockery::mock(ContaboService::class);
    $mock->shouldReceive('getInstance')->never();
    app()->instance(ContaboService::class, $mock);

    setupVpsControllerGates($user);

    $response = $this->actingAs($user)
        ->get(route('user.vps.show', $subscription));

    $response->assertSuccessful();
    $response->assertViewHas('errorMessage', 'No VPS instance linked to this subscription yet. It will be assigned shortly.');
});

it('user vps show handles RuntimeException from provider getInstance', function (): void {
    $user = createVpsControllerUser(['vps_show']);

    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'provider_resource_id' => '12345',
    ]);

    $mock = Mockery::mock(ContaboService::class);
    $mock->shouldReceive('getInstance')->once()->andThrow(new RuntimeException('API Error'));
    app()->instance(ContaboService::class, $mock);

    setupVpsControllerGates($user);

    $response = $this->actingAs($user)
        ->get(route('user.vps.show', $subscription));

    $response->assertSuccessful();
    $response->assertViewHas('errorMessage', 'Failed to load VPS instance details.');
});

it('user vps action endpoints succeed', function (): void {
    $user = createVpsControllerUser([
        'vps_access',
        'vps_show',
        'vps_snapshot_access',
        'vps_restart',
        'vps_shutdown',
        'vps_rescue',
        'vps_reset_credentials',
        'vps_change_display_name',
        'vps_snapshot_create',
        'vps_snapshot_delete',
        'vps_backup_restore',
        'vps_backup_access',
        'vps_reinstall',
        'vps_upgrade',
    ]);

    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'provider_resource_id' => '12345',
    ]);

    $mock = Mockery::mock(ContaboService::class);
    $mock->shouldReceive('getInstance')->andReturn(makeVpsControllerApiInstance());
    $mock->shouldReceive('listSnapshots')->with(12345)->once()->andReturn(['data' => []]);
    $mock->shouldReceive('listInstanceBackups')->with(12345)->once()->andReturn(['data' => []]);

    $mock->shouldReceive('restartInstance')->with(12345)->once()->andReturn([]);
    $mock->shouldReceive('shutdownInstance')->with(12345)->once()->andReturn([]);
    $mock->shouldReceive('createSecret')
        ->withArgs(fn (array $payload): bool => $payload['type'] === 'password')
        ->twice()
        ->andReturn(['secretId' => 99999]);
    $mock->shouldReceive('rescueInstance')->with(12345, ['rootPassword' => 99999])->once()->andReturn([]);
    $mock->shouldReceive('resetInstancePassword')->with(12345, ['rootPassword' => 99999])->once()->andReturn([]);
    $mock->shouldReceive('updateInstance')->with(12345, 'New Name')->once()->andReturn([]);

    $mock->shouldReceive('createSnapshot')->with(12345, 'user-snapshot', '')
        ->once()
        ->andReturn(['snapshotId' => 'snap-123']);

    $mock->shouldReceive('deleteSnapshot')->with(12345, 'snap-123')->once()->andReturn(true);
    $mock->shouldReceive('revertSnapshot')->with(12345, 'snap-123')->once()->andReturn([]);

    $mock->shouldReceive('reinstallInstance')->with(12345, ['imageId' => 'ubuntu-22.04'])->once()->andReturn([]);

    app()->instance(ContaboService::class, $mock);
    setupVpsControllerGates($user);

    $this->actingAs($user)
        ->get(route('user.vps.show', $subscription))
        ->assertSuccessful();

    $this->actingAs($user)
        ->from(route('user.vps.show', $subscription))
        ->post(route('user.vps.restart', $subscription))
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->actingAs($user)
        ->from(route('user.vps.show', $subscription))
        ->post(route('user.vps.shutdown', $subscription))
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->actingAs($user)
        ->from(route('user.vps.show', $subscription))
        ->post(route('user.vps.rescue', $subscription))
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->actingAs($user)
        ->from(route('user.vps.show', $subscription))
        ->post(route('user.vps.reset-credentials', $subscription))
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->actingAs($user)
        ->from(route('user.vps.show', $subscription))
        ->post(route('user.vps.display-name', $subscription), [
            'display_name' => 'New Name',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->actingAs($user)
        ->from(route('user.vps.show', $subscription))
        ->post(route('user.vps.snapshots.store', $subscription), [
            'name' => 'user-snapshot',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->actingAs($user)
        ->from(route('user.vps.show', $subscription))
        ->delete(route('user.vps.snapshots.destroy', [$subscription, 'snap-123']))
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->actingAs($user)
        ->from(route('user.vps.show', $subscription))
        ->post(route('user.vps.snapshots.restore', [$subscription, 'snap-123']))
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->actingAs($user)
        ->from(route('user.vps.show', $subscription))
        ->post(route('user.vps.reinstall', $subscription), [
            'imageId' => 'ubuntu-22.04',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');
});

it('non-admin with vps_access permission cannot access admin vps index', function (): void {
    $user = createNonAdminVpsUser(['vps_access']);
    setupVpsControllerGates($user);

    $response = $this->actingAs($user)
        ->get(route('admin.vps.index'));

    $response->assertForbidden();
});

it('admin can view details of an unassigned instance by instance id', function (): void {
    $user = createVpsControllerUser(['vps_access']);

    $mock = Mockery::mock(ContaboService::class);
    $mock->shouldReceive('getInstance')
        ->with(12345)
        ->once()
        ->andReturn(makeVpsControllerApiInstance(['instanceId' => 12345]));
    app()->instance(ContaboService::class, $mock);

    setupVpsControllerGates($user);

    $response = $this->actingAs($user)
        ->get(route('admin.vps.instance.show', 12345));

    $response->assertSuccessful();
    $response->assertViewHas('instanceId', 12345);
    $response->assertViewHas('instance');
});

it('admin showInstance handles RuntimeException from provider', function (): void {
    $user = createVpsControllerUser(['vps_access']);

    $mock = Mockery::mock(ContaboService::class);
    $mock->shouldReceive('getInstance')
        ->with(99999)
        ->once()
        ->andThrow(new RuntimeException('API Error'));
    app()->instance(ContaboService::class, $mock);

    setupVpsControllerGates($user);

    $response = $this->actingAs($user)
        ->get(route('admin.vps.instance.show', 99999));

    $response->assertSuccessful();
    $response->assertViewHas('errorMessage', 'Failed to load VPS instance details.');
});
