<?php

declare(strict_types=1);

use App\Enums\CourtStatus;
use App\Models\Court;
use App\Models\User;

test('guests are redirected from courts index', function (): void {
    $response = $this->get(route('admin.courts.index'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can view courts index', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('admin.courts.index'));
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('admin/courts/index'));
});

test('authenticated users can create a court', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->post(route('admin.courts.store'), [
        'name' => 'Test Court',
        'country' => 'United Kingdom',
        'city' => 'London',
        'latitude' => 51.5074,
        'longitude' => -0.1278,
        'status' => CourtStatus::ACTIVE->value,
    ]);

    $response->assertRedirect(route('admin.courts.index'));

    $this->assertDatabaseHas('courts', [
        'name' => 'Test Court',
        'country' => 'United Kingdom',
        'city' => 'London',
        'status' => CourtStatus::ACTIVE->value,
        'created_by' => $user->id,
    ]);
});

test('create court validates required fields', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->post(route('admin.courts.store'), []);

    $response->assertInvalid(['name', 'country', 'city', 'status']);
});

test('authenticated users can update a court', function (): void {
    $user = User::factory()->create();
    $court = Court::factory()->create(['created_by' => $user->id]);
    $this->actingAs($user);

    $response = $this->patch(route('admin.courts.update', $court), [
        'name' => 'Updated Court',
        'country' => 'France',
        'city' => 'Paris',
        'latitude' => 48.8566,
        'longitude' => 2.3522,
        'status' => CourtStatus::PILOT->value,
    ]);

    $response->assertRedirect(route('admin.courts.index'));

    $this->assertDatabaseHas('courts', [
        'id' => $court->id,
        'name' => 'Updated Court',
        'country' => 'France',
        'city' => 'Paris',
        'status' => CourtStatus::PILOT->value,
    ]);
});

test('courts index can be filtered by search term', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $matching = Court::factory()->create(['name' => 'Wimbledon Centre Court', 'created_by' => $user->id]);
    $other = Court::factory()->create(['name' => 'Roland Garros', 'created_by' => $user->id]);

    $response = $this->get(route('admin.courts.index', ['search' => 'Wimbledon']));

    $response->assertOk();
    $response->assertInertia(
        fn ($page) => $page
            ->component('admin/courts/index')
            ->where('filters.search', 'Wimbledon')
            ->has('courts.data', 1)
            ->where('courts.data.0.id', $matching->id)
    );
});

test('authenticated users can delete a court', function (): void {
    $user = User::factory()->create();
    $court = Court::factory()->create(['created_by' => $user->id]);
    $this->actingAs($user);

    $response = $this->delete(route('admin.courts.destroy', $court));

    $response->assertRedirect();

    $this->assertDatabaseMissing('courts', [
        'id' => $court->id,
    ]);
});
