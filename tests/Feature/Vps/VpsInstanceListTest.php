<?php

declare(strict_types=1);

use App\Http\Middleware\AuthGates;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Vps\ContaboService;
use Illuminate\Http\Request;

function createVpsUser(array $permissions = ['vps_access']): User
{
    $role = Role::query()->create(['title' => 'VpsUser-'.uniqid()]);
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

function setupVpsGates(User $user): void
{
    auth()->login($user);
    (new AuthGates)->handle(Request::create('/'), fn ($req) => $req);
}

function fakeContaboInstances(array $instances = []): void
{
    $defaultInstance = [
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

    if ($instances === []) {
        $instances = [$defaultInstance];
    }

    $mock = Mockery::mock(ContaboService::class);
    $mock->shouldReceive('listInstances')->andReturn(['data' => $instances]);
    $mock->shouldReceive('getInstance')->andReturn($instances[0] ?? $defaultInstance);
    $mock->shouldReceive('restartInstance')->andReturn($instances[0] ?? $defaultInstance);
    $mock->shouldReceive('shutdownInstance')->andReturn($instances[0] ?? $defaultInstance);

    app()->instance(ContaboService::class, $mock);
}

it('shows VPS instance list for authorized users', function (): void {
    $user = createVpsUser(['vps_access']);
    Subscription::factory()->create([
        'user_id' => $user->id,
        'provider_resource_id' => '12345',
    ]);

    fakeContaboInstances();
    setupVpsGates($user);

    $this->actingAs($user)
        ->get(route('admin.vps.index'))
        ->assertOk()
        ->assertSee('My VPS')
        ->assertSee('192.168.1.1');
});

it('shows empty state when user has no VPS instances', function (): void {
    $user = createVpsUser(['vps_access']);

    fakeContaboInstances();
    setupVpsGates($user);

    $this->actingAs($user)
        ->get(route('user.vps.index'))
        ->assertOk()
        ->assertSee('No VPS instances found');
});

it('denies access to users without vps_access permission', function (): void {
    $user = User::factory()->create();
    setupVpsGates($user);

    $this->actingAs($user)
        ->get(route('admin.vps.index'))
        ->assertForbidden();
});

it('scopes instances to current user in user mode', function (): void {
    $user = createVpsUser(['vps_access']);
    $otherUser = User::factory()->create();

    Subscription::factory()->create([
        'user_id' => $otherUser->id,
        'provider_resource_id' => '99999',
    ]);

    fakeContaboInstances();
    setupVpsGates($user);

    $this->actingAs($user)
        ->get(route('user.vps.index'))
        ->assertOk()
        ->assertSee('No VPS instances found');
});
