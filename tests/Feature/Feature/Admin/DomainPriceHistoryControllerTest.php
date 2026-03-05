<?php

declare(strict_types=1);

use App\Models\TldPricing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('requires authentication', function (): void {
    $this->get(route('admin.domain-price-history.index'))
        ->assertRedirect(route('login'));
});

it('loads the index page for an authenticated user', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.domain-price-history.index'))
        ->assertSuccessful()
        ->assertViewIs('admin.domain-price-history.index');
});

it('shows history records on the index page', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $tldPricing = TldPricing::factory()->create(['register_price' => 10]);
    $tldPricing->update(['register_price' => 20]);

    $this->actingAs($user)
        ->get(route('admin.domain-price-history.index'))
        ->assertSuccessful()
        ->assertViewHas('histories');
});

it('filters by search term (TLD name)', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $tldPricing = TldPricing::factory()->create(['register_price' => 10]);
    $tldPricing->update(['register_price' => 20]);

    $tldName = $tldPricing->tld->name;

    $response = $this->actingAs($user)
        ->get(route('admin.domain-price-history.index', ['search' => $tldName]));

    $response->assertSuccessful();

    $histories = $response->viewData('histories');

    expect($histories->total())->toBeGreaterThanOrEqual(1);
});

it('returns empty results for a search that matches nothing', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('admin.domain-price-history.index', ['search' => 'nonexistent-tld-zzz']));

    $response->assertSuccessful();

    $histories = $response->viewData('histories');

    expect($histories->total())->toBe(0);
});

it('filters by changed_by user', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $this->actingAs($user);
    $tldPricing = TldPricing::factory()->create(['register_price' => 10]);
    $tldPricing->update(['register_price' => 20]);

    $this->actingAs($otherUser);
    $otherPricing = TldPricing::factory()->create(['register_price' => 10]);
    $otherPricing->update(['register_price' => 30]);

    $response = $this->actingAs($user)
        ->get(route('admin.domain-price-history.index', ['changed_by' => $user->id]));

    $response->assertSuccessful();

    $histories = $response->viewData('histories');

    expect($histories->every(fn ($h): bool => $h->changed_by === $user->id))->toBeTrue();
});

it('passes filters and userOptions to the view', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('admin.domain-price-history.index', [
            'search' => 'com',
            'date_from' => '2025-01-01',
            'date_to' => '2025-12-31',
            'per_page' => 10,
        ]));

    $response->assertSuccessful()
        ->assertViewHas('filters')
        ->assertViewHas('userOptions');

    $filters = $response->viewData('filters');

    expect($filters['search'])->toBe('com')
        ->and($filters['date_from'])->toBe('2025-01-01')
        ->and($filters['date_to'])->toBe('2025-12-31')
        ->and($filters['per_page'])->toBe(10);
});
