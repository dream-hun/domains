<?php

declare(strict_types=1);

use App\Http\Middleware\AuthGates;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Vps\ContaboService;
use Illuminate\Http\Request;

function createVpsDetailUser(array $permissions = ['vps_access', 'vps_show']): User
{
    $role = Role::query()->create(['title' => 'VpsDetailUser-'.uniqid()]);
    foreach ($permissions as $permission) {
        $role->permissions()->attach(
            Permission::query()->where('title', $permission)->first()?->id
                ?? Permission::query()->create(['title' => $permission])->id
        );
    }
    $user = User::factory()->create();
    $user->roles()->attach($role);

    return $user;
}

function setupVpsDetailGates(User $user): void
{
    auth()->login($user);
    (new AuthGates)->handle(Request::create('/'), fn ($req) => $req);
}

function fakeContaboInstanceDetail(): void
{
    $instance = [
        'instanceId' => 12345,
        'name' => 'vmi123456',
        'displayName' => 'My VPS',
        'status' => 'running',
        'productType' => 'V45',
        'defaultUser' => 'root',
        'region' => 'EU',
        'dataCenter' => 'European Union (Germany)',
        'ipConfig' => ['v4' => ['ip' => '192.168.1.1'], 'v6' => ['ip' => '::1']],
        'osType' => 'Linux',
        'cpuCores' => 4,
        'ramMb' => 8192,
        'diskMb' => 204800,
        'imageId' => 'ubuntu-22.04',
        'createdDate' => '2025-01-01T00:00:00.000Z',
    ];

    $mock = Mockery::mock(ContaboService::class);
    $mock->shouldReceive('getInstance')->andReturn($instance);
    $mock->shouldReceive('listSnapshots')->andReturn(['data' => []]);
    $mock->shouldReceive('restartInstance')->andReturn($instance);
    $mock->shouldReceive('shutdownInstance')->andReturn($instance);
    $mock->shouldReceive('rescueInstance')->andReturn($instance);
    $mock->shouldReceive('resetInstancePassword')->andReturn($instance);
    $mock->shouldReceive('updateInstance')->andReturn($instance);

    app()->instance(ContaboService::class, $mock);
}

it('shows VPS instance details for authorized user', function (): void {
    $user = createVpsDetailUser(['vps_access', 'vps_show', 'vps_snapshot_access']);
    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'provider_resource_id' => '12345',
    ]);

    fakeContaboInstanceDetail();
    setupVpsDetailGates($user);

    $this->actingAs($user)
        ->get(route('admin.vps.show', $subscription))
        ->assertOk()
        ->assertSee('My VPS')
        ->assertSee('192.168.1.1');
});

it('denies access to users without vps_show permission', function (): void {
    $user = User::factory()->create();
    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'provider_resource_id' => '12345',
    ]);

    setupVpsDetailGates($user);

    $this->actingAs($user)
        ->get(route('admin.vps.show', $subscription))
        ->assertForbidden();
});

it('denies access to other users VPS in user mode', function (): void {
    $owner = User::factory()->create();
    $otherUser = createVpsDetailUser(['vps_access', 'vps_show']);
    $subscription = Subscription::factory()->create([
        'user_id' => $owner->id,
        'provider_resource_id' => '12345',
    ]);

    fakeContaboInstanceDetail();
    setupVpsDetailGates($otherUser);

    $this->actingAs($otherUser)
        ->get(route('user.vps.show', $subscription))
        ->assertForbidden();
});

it('can restart instance from detail page', function (): void {
    $user = createVpsDetailUser(['vps_access', 'vps_show', 'vps_restart', 'vps_snapshot_access']);
    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'provider_resource_id' => '12345',
    ]);

    fakeContaboInstanceDetail();
    setupVpsDetailGates($user);

    $this->actingAs($user)
        ->post(route('admin.vps.restart', $subscription))
        ->assertRedirect()
        ->assertSessionHas('success');
});
