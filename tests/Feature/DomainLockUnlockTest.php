<?php

declare(strict_types=1);

use App\Models\Domain;
use App\Models\DomainPrice;
use App\Models\User;
use App\Enums\DomainType;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can lock and unlock a local domain', function () {
    $user = User::factory()->create();
    $price = DomainPrice::factory()->create(['type' => DomainType::Local]);
    $domain = Domain::factory()->create([
        'owner_id' => $user->id,
        'domain_price_id' => $price->id,
        'is_locked' => false,
    ]);

    $this->actingAs($user)
        ->post(route('admin.domains.lock', $domain), [
            'domain_id' => $domain->id,
            'lock' => true,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($domain->fresh()->is_locked)->toBeTrue();

    $this->post(route('admin.domains.lock', $domain), [
        'domain_id' => $domain->id,
        'lock' => false,
    ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($domain->fresh()->is_locked)->toBeFalse();
});

it('can lock and unlock an international domain', function () {
    $user = User::factory()->create();
    $price = DomainPrice::factory()->create(['type' => DomainType::International]);
    $domain = Domain::factory()->create([
        'owner_id' => $user->id,
        'domain_price_id' => $price->id,
        'is_locked' => false,
    ]);

    $this->actingAs($user)
        ->post(route('admin.domains.lock', $domain), [
            'domain_id' => $domain->id,
            'lock' => true,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($domain->fresh()->is_locked)->toBeTrue();

    $this->post(route('admin.domains.lock', $domain), [
        'domain_id' => $domain->id,
        'lock' => false,
    ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($domain->fresh()->is_locked)->toBeFalse();
});

