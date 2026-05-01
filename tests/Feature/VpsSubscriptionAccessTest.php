<?php

declare(strict_types=1);

use App\Http\Middleware\AuthGates;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Vps\ContaboService;
use Illuminate\Http\Request;

function createVpsAccessUser(array $permissions = ['vps_access', 'vps_restart']): User
{
    $role = Role::query()->create(['title' => 'VpsAccess-'.uniqid()]);
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

function setupVpsAccessGates(User $user): void
{
    auth()->login($user);
    (new AuthGates)->handle(Request::create('/'), fn ($req) => $req);
}

function fakeVpsServiceForAccess(): void
{
    $instance = [
        'instanceId' => 12345,
        'name' => 'vmi123456',
        'displayName' => 'My VPS',
        'status' => 'running',
        'productType' => 'V45',
        'defaultUser' => 'root',
        'ipConfig' => ['v4' => ['ip' => '192.168.1.1'], 'v6' => ['ip' => '::1']],
        'osType' => 'Linux',
        'cpuCores' => 4,
        'ramMb' => 8192,
        'diskMb' => 204800,
        'imageId' => 'ubuntu-22.04',
        'createdDate' => '2025-01-01T00:00:00.000Z',
    ];

    $mock = Mockery::mock(ContaboService::class);
    $mock->shouldReceive('listAllInstances')->andReturn([$instance]);
    $mock->shouldReceive('getInstance')->andReturn($instance);
    $mock->shouldReceive('listSnapshots')->andReturn(['data' => []]);
    $mock->shouldReceive('listInstanceBackups')->andReturn(['data' => []]);
    $mock->shouldReceive('restartInstance')->andReturn(['success' => true]);

    app()->instance(ContaboService::class, $mock);
}

// --- Index ---

it('shows VPS instances for a user with an active subscription and assigned VPS', function (): void {
    $user = createVpsAccessUser();
    Subscription::factory()->active()->create([
        'user_id' => $user->id,
        'provider_resource_id' => '12345',
    ]);

    fakeVpsServiceForAccess();
    setupVpsAccessGates($user);

    $this->actingAs($user)
        ->get(route('user.vps.index'))
        ->assertOk()
        ->assertSee('My VPS');
});

it('does not show VPS instances for expired subscriptions on the index', function (): void {
    $user = createVpsAccessUser();
    Subscription::factory()->expired()->create([
        'user_id' => $user->id,
        'provider_resource_id' => '12345',
    ]);

    fakeVpsServiceForAccess();
    setupVpsAccessGates($user);

    $this->actingAs($user)
        ->get(route('user.vps.index'))
        ->assertOk()
        ->assertSee('No VPS instances found');
});

it('does not show VPS instances for cancelled subscriptions on the index', function (): void {
    $user = createVpsAccessUser();
    Subscription::factory()->cancelled()->create([
        'user_id' => $user->id,
        'provider_resource_id' => '12345',
    ]);

    fakeVpsServiceForAccess();
    setupVpsAccessGates($user);

    $this->actingAs($user)
        ->get(route('user.vps.index'))
        ->assertOk()
        ->assertSee('No VPS instances found');
});

it('does not show subscriptions without a VPS assigned on the index', function (): void {
    $user = createVpsAccessUser();
    Subscription::factory()->active()->create([
        'user_id' => $user->id,
        'provider_resource_id' => null,
    ]);

    fakeVpsServiceForAccess();
    setupVpsAccessGates($user);

    $this->actingAs($user)
        ->get(route('user.vps.index'))
        ->assertOk()
        ->assertSee('No VPS instances found');
});

// --- Show ---

it('allows access to VPS detail for an active subscription with VPS assigned', function (): void {
    $user = createVpsAccessUser();
    $subscription = Subscription::factory()->active()->create([
        'user_id' => $user->id,
        'provider_resource_id' => '12345',
    ]);

    fakeVpsServiceForAccess();
    setupVpsAccessGates($user);

    $this->actingAs($user)
        ->get(route('user.vps.show', $subscription))
        ->assertOk();
});

it('forbids access to VPS detail for an expired subscription', function (): void {
    $user = createVpsAccessUser();
    $subscription = Subscription::factory()->expired()->create([
        'user_id' => $user->id,
        'provider_resource_id' => '12345',
    ]);

    fakeVpsServiceForAccess();
    setupVpsAccessGates($user);

    $this->actingAs($user)
        ->get(route('user.vps.show', $subscription))
        ->assertForbidden();
});

it('forbids access to VPS detail for a cancelled subscription', function (): void {
    $user = createVpsAccessUser();
    $subscription = Subscription::factory()->cancelled()->create([
        'user_id' => $user->id,
        'provider_resource_id' => '12345',
    ]);

    fakeVpsServiceForAccess();
    setupVpsAccessGates($user);

    $this->actingAs($user)
        ->get(route('user.vps.show', $subscription))
        ->assertForbidden();
});

// --- Actions (restart as representative) ---

it('allows restart on an active subscription with VPS assigned', function (): void {
    $user = createVpsAccessUser(['vps_access', 'vps_restart']);
    $subscription = Subscription::factory()->active()->create([
        'user_id' => $user->id,
        'provider_resource_id' => '12345',
    ]);

    fakeVpsServiceForAccess();
    setupVpsAccessGates($user);

    $this->actingAs($user)
        ->post(route('user.vps.restart', $subscription))
        ->assertRedirect();
});

it('forbids restart on an expired subscription', function (): void {
    $user = createVpsAccessUser(['vps_access', 'vps_restart']);
    $subscription = Subscription::factory()->expired()->create([
        'user_id' => $user->id,
        'provider_resource_id' => '12345',
    ]);

    fakeVpsServiceForAccess();
    setupVpsAccessGates($user);

    $this->actingAs($user)
        ->post(route('user.vps.restart', $subscription))
        ->assertForbidden();
});

it('forbids restart on a cancelled subscription', function (): void {
    $user = createVpsAccessUser(['vps_access', 'vps_restart']);
    $subscription = Subscription::factory()->cancelled()->create([
        'user_id' => $user->id,
        'provider_resource_id' => '12345',
    ]);

    fakeVpsServiceForAccess();
    setupVpsAccessGates($user);

    $this->actingAs($user)
        ->post(route('user.vps.restart', $subscription))
        ->assertForbidden();
});

it('forbids restart on an active subscription without VPS assigned', function (): void {
    $user = createVpsAccessUser(['vps_access', 'vps_restart']);
    $subscription = Subscription::factory()->active()->create([
        'user_id' => $user->id,
        'provider_resource_id' => null,
    ]);

    fakeVpsServiceForAccess();
    setupVpsAccessGates($user);

    $this->actingAs($user)
        ->post(route('user.vps.restart', $subscription))
        ->assertForbidden();
});

it('forbids restart on another user subscription', function (): void {
    $owner = User::factory()->create();
    $user = createVpsAccessUser(['vps_access', 'vps_restart']);
    $subscription = Subscription::factory()->active()->create([
        'user_id' => $owner->id,
        'provider_resource_id' => '12345',
    ]);

    fakeVpsServiceForAccess();
    setupVpsAccessGates($user);

    $this->actingAs($user)
        ->post(route('user.vps.restart', $subscription))
        ->assertForbidden();
});
