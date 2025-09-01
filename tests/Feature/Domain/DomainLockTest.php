<?php

declare(strict_types=1);

namespace Tests\Feature\Domain;

use App\Models\Domain;
use App\Models\User;
use App\Services\Domain\DomainServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\mock;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->domain = Domain::factory()->create([
        'owner_id' => $this->user->id,
        'is_locked' => false,
    ]);
});

it('can lock a domain', function () {
    $domainService = mock(DomainServiceInterface::class);
    $domainService->shouldReceive('setDomainLock')
        ->once()
        ->with($this->domain->name, true)
        ->andReturn(['success' => true]);

    actingAs($this->user)
        ->post(route('admin.domains.lock', $this->domain), [
            'lock' => true,
        ])
        ->assertRedirect()
        ->assertSessionHas('success', 'Domain locked successfully');

    expect($this->domain->fresh()->is_locked)->toBeTrue();
});

it('can unlock a domain', function () {
    $this->domain->update(['is_locked' => true]);

    $domainService = mock(DomainServiceInterface::class);
    $domainService->shouldReceive('setDomainLock')
        ->once()
        ->with($this->domain->name, false)
        ->andReturn(['success' => true]);

    actingAs($this->user)
        ->post(route('admin.domains.lock', $this->domain), [
            'lock' => false,
        ])
        ->assertRedirect()
        ->assertSessionHas('success', 'Domain unlocked successfully');

    expect($this->domain->fresh()->is_locked)->toBeFalse();
});

it('handles domain service failures', function () {
    $domainService = mock(DomainServiceInterface::class);
    $domainService->shouldReceive('setDomainLock')
        ->once()
        ->with($this->domain->name, true)
        ->andReturn([
            'success' => false,
            'message' => 'Service unavailable',
        ]);

    actingAs($this->user)
        ->post(route('admin.domains.lock', $this->domain), [
            'lock' => true,
        ])
        ->assertRedirect()
        ->assertSessionHasErrors(['error' => 'Service unavailable']);

    expect($this->domain->fresh()->is_locked)->toBeFalse();
});
