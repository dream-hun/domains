<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Jobs\RecalculateRankingsJob;
use App\Models\RankingConfiguration;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Permission;

beforeEach(function (): void {
    foreach (Role::cases() as $role) {
        Spatie\Permission\Models\Role::query()->firstOrCreate(['name' => $role->value]);
    }

    RankingConfiguration::query()->create([
        'win_weight' => 3.0,
        'loss_weight' => 1.0,
        'game_count_weight' => 0.5,
        'frequency_weight' => 2.0,
    ]);
});

test('guest is redirected from ranking edit', function (): void {
    $response = $this->get(route('admin.ranking.edit'));
    $response->assertRedirect(route('login'));
});

test('player cannot access ranking config', function (): void {
    $player = User::factory()->create()->assignRole(Role::Player->value);
    $this->actingAs($player);

    $response = $this->get(route('admin.ranking.edit'));
    $response->assertForbidden();
});

test('moderator cannot access ranking config', function (): void {
    Permission::query()->firstOrCreate(['name' => 'moderate-games']);
    $moderator = User::factory()->create()->assignRole(Role::Moderator->value);
    $this->actingAs($moderator);

    $response = $this->get(route('admin.ranking.edit'));
    $response->assertForbidden();
});

test('admin can view ranking edit page', function (): void {
    $admin = User::factory()->create()->assignRole(Role::Administrator->value);
    $this->actingAs($admin);

    $response = $this->get(route('admin.ranking.edit'));

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->component('admin/ranking/edit')
        ->has('config')
        ->has('config.win_weight')
        ->has('config.loss_weight')
        ->has('config.game_count_weight')
        ->has('config.frequency_weight')
    );
});

test('admin can save new ranking weights', function (): void {
    Queue::fake();
    $admin = User::factory()->create()->assignRole(Role::Administrator->value);
    $this->actingAs($admin);

    $response = $this->post(route('admin.ranking.update'), [
        'win_weight' => 4.0,
        'loss_weight' => 0.5,
        'game_count_weight' => 1.0,
        'frequency_weight' => 3.0,
    ]);

    $response->assertRedirect(route('admin.ranking.edit'));

    $this->assertDatabaseHas('ranking_configurations', [
        'win_weight' => 4.0,
        'loss_weight' => 0.5,
        'game_count_weight' => 1.0,
        'frequency_weight' => 3.0,
        'updated_by' => $admin->id,
    ]);
});

test('old config row is preserved when saving new weights', function (): void {
    Queue::fake();
    $admin = User::factory()->create()->assignRole(Role::Administrator->value);
    $this->actingAs($admin);

    $originalCount = RankingConfiguration::query()->count();

    $this->post(route('admin.ranking.update'), [
        'win_weight' => 4.0,
        'loss_weight' => 0.5,
        'game_count_weight' => 1.0,
        'frequency_weight' => 3.0,
    ]);

    expect(RankingConfiguration::query()->count())->toBe($originalCount + 1);
});

test('job is dispatched after saving new config', function (): void {
    Queue::fake();
    $admin = User::factory()->create()->assignRole(Role::Administrator->value);
    $this->actingAs($admin);

    $this->post(route('admin.ranking.update'), [
        'win_weight' => 4.0,
        'loss_weight' => 0.5,
        'game_count_weight' => 1.0,
        'frequency_weight' => 3.0,
    ]);

    Queue::assertPushed(RecalculateRankingsJob::class);
});

test('validation rejects missing weights', function (): void {
    $admin = User::factory()->create()->assignRole(Role::Administrator->value);
    $this->actingAs($admin);

    $response = $this->post(route('admin.ranking.update'), []);

    $response->assertInvalid(['win_weight', 'loss_weight', 'game_count_weight', 'frequency_weight']);
});

test('validation rejects weights above 100', function (): void {
    $admin = User::factory()->create()->assignRole(Role::Administrator->value);
    $this->actingAs($admin);

    $response = $this->post(route('admin.ranking.update'), [
        'win_weight' => 101,
        'loss_weight' => 101,
        'game_count_weight' => 101,
        'frequency_weight' => 101,
    ]);

    $response->assertInvalid(['win_weight', 'loss_weight', 'game_count_weight', 'frequency_weight']);
});

test('validation rejects negative weights', function (): void {
    $admin = User::factory()->create()->assignRole(Role::Administrator->value);
    $this->actingAs($admin);

    $response = $this->post(route('admin.ranking.update'), [
        'win_weight' => -1,
        'loss_weight' => -1,
        'game_count_weight' => -1,
        'frequency_weight' => -1,
    ]);

    $response->assertInvalid(['win_weight', 'loss_weight', 'game_count_weight', 'frequency_weight']);
});
