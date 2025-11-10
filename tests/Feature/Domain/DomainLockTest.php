<?php

declare(strict_types=1);

namespace Tests\Feature\Domain;

use App\Models\Domain;
use App\Models\DomainPrice;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Domain\DomainServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\mock;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Create permissions and role
    $permission = Permission::query()->create(['title' => 'domain_edit']);
    $role = Role::query()->create(['title' => 'Admin']);
    $role->permissions()->attach($permission->id);

    $this->user = User::factory()->create();
    $this->user->roles()->attach($role->id);

    // Create a domain price first
    $this->domainPrice = DomainPrice::factory()->create([
        'tld' => '.com',
        'register_price' => 1000,
        'renewal_price' => 1000,
    ]);

    $this->domain = Domain::factory()->create([
        'owner_id' => $this->user->id,
        'domain_price_id' => $this->domainPrice->id,
        'is_locked' => false,
    ]);
});

it('can lock a domain', function (): void {
    $domainService = mock(DomainServiceInterface::class);
    $domainService->shouldReceive('setDomainLock')
        ->once()
        ->with($this->domain->name, true)
        ->andReturn(['success' => true]);

    actingAs($this->user)
        ->put(route('admin.domains.lock', $this->domain))
        ->assertRedirect()
        ->assertSessionHas('success', 'Domain locked successfully');

    expect($this->domain->fresh()->is_locked)->toBeTrue();
});

it('can unlock a domain', function (): void {
    $this->domain->update(['is_locked' => true]);

    $domainService = mock(DomainServiceInterface::class);
    $domainService->shouldReceive('setDomainLock')
        ->once()
        ->with($this->domain->name, false)
        ->andReturn(['success' => true]);

    actingAs($this->user)
        ->put(route('admin.domains.lock', $this->domain))
        ->assertRedirect()
        ->assertSessionHas('success', 'Domain unlocked successfully');

    expect($this->domain->fresh()->is_locked)->toBeFalse();
});

it('handles domain service failures', function (): void {
    $domainService = mock(DomainServiceInterface::class);
    $domainService->shouldReceive('setDomainLock')
        ->once()
        ->with($this->domain->name, true)
        ->andReturn([
            'success' => false,
            'message' => 'Service unavailable',
        ]);

    actingAs($this->user)
        ->put(route('admin.domains.lock', $this->domain))
        ->assertRedirect()
        ->assertSessionHasErrors(['error' => 'Service unavailable']);

    expect($this->domain->fresh()->is_locked)->toBeFalse();
});
