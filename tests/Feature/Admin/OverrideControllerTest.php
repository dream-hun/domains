<?php

declare(strict_types=1);

use App\Models\Game;
use App\Models\GameModeration;
use App\Models\RankingConfiguration;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Permission;

beforeEach(function (): void {
    Permission::query()->firstOrCreate(['name' => 'override-moderation']);
    Queue::fake();

    RankingConfiguration::query()->create([
        'win_weight' => 3.0,
        'loss_weight' => 1.0,
        'game_count_weight' => 0.5,
        'frequency_weight' => 2.0,
    ]);
});

test('admin can view the flagged games index', function (): void {
    $admin = User::factory()->create()->givePermissionTo('override-moderation');
    $this->actingAs($admin);

    Game::factory()->create(['status' => 'flagged']);

    $response = $this->get(route('admin.override.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('admin/override/index'));
});

test('flagged games index only shows flagged games', function (): void {
    $admin = User::factory()->create()->givePermissionTo('override-moderation');
    $this->actingAs($admin);

    $flagged = Game::factory()->create(['status' => 'flagged']);
    Game::factory()->create(['status' => 'pending']);
    Game::factory()->create(['status' => 'approved']);

    $response = $this->get(route('admin.override.index'));

    $response->assertOk();
    $response->assertInertia(
        fn ($page) => $page
            ->component('admin/override/index')
            ->has('games.data', 1)
            ->where('games.data.0.id', $flagged->id)
    );
});

test('admin can view the override show page', function (): void {
    $admin = User::factory()->create()->givePermissionTo('override-moderation');
    $game = Game::factory()->create(['status' => 'flagged']);
    $this->actingAs($admin);

    $response = $this->get(route('admin.override.show', $game));

    $response->assertOk();
    $response->assertInertia(
        fn ($page) => $page
            ->component('admin/override/show')
            ->where('game.id', $game->id)
            ->has('game.player')
            ->has('game.court')
            ->has('game.moderation')
    );
});

test('show page returns 404 for non-existent game', function (): void {
    $admin = User::factory()->create()->givePermissionTo('override-moderation');
    $this->actingAs($admin);

    $response = $this->get(route('admin.override.show', 99999));

    $response->assertNotFound();
});

test('admin can approve a flagged game', function (): void {
    $admin = User::factory()->create()->givePermissionTo('override-moderation');
    $game = Game::factory()->create(['status' => 'flagged']);
    $this->actingAs($admin);

    $response = $this->patch(route('admin.override.update', $game), [
        'status' => 'approved',
        'reason' => 'Reviewed carefully — meets all standards.',
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('game_moderations', [
        'game_id' => $game->id,
        'moderator_id' => $admin->id,
        'status' => 'approved',
        'reason' => 'Reviewed carefully — meets all standards.',
        'is_override' => true,
    ]);

    $this->assertDatabaseHas('games', [
        'id' => $game->id,
        'status' => 'approved',
    ]);
});

test('admin can reject a flagged game', function (): void {
    $admin = User::factory()->create()->givePermissionTo('override-moderation');
    $game = Game::factory()->create(['status' => 'flagged']);
    $this->actingAs($admin);

    $response = $this->patch(route('admin.override.update', $game), [
        'status' => 'rejected',
        'reason' => 'Content violates policy.',
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('game_moderations', [
        'game_id' => $game->id,
        'moderator_id' => $admin->id,
        'status' => 'rejected',
        'reason' => 'Content violates policy.',
        'is_override' => true,
    ]);

    $this->assertDatabaseHas('games', [
        'id' => $game->id,
        'status' => 'rejected',
    ]);
});

test('override cannot use flagged status', function (): void {
    $admin = User::factory()->create()->givePermissionTo('override-moderation');
    $game = Game::factory()->create(['status' => 'flagged']);
    $this->actingAs($admin);

    $response = $this->patch(route('admin.override.update', $game), [
        'status' => 'flagged',
        'reason' => 'Still not sure.',
    ]);

    $response->assertInvalid(['status']);
});

test('reason is required for override decision', function (): void {
    $admin = User::factory()->create()->givePermissionTo('override-moderation');
    $game = Game::factory()->create(['status' => 'flagged']);
    $this->actingAs($admin);

    $response = $this->patch(route('admin.override.update', $game), [
        'status' => 'approved',
    ]);

    $response->assertInvalid(['reason']);
});

test('status is required for override decision', function (): void {
    $admin = User::factory()->create()->givePermissionTo('override-moderation');
    $game = Game::factory()->create(['status' => 'flagged']);
    $this->actingAs($admin);

    $response = $this->patch(route('admin.override.update', $game), [
        'reason' => 'Some reason.',
    ]);

    $response->assertInvalid(['status']);
});

test('update redirects to override index on success', function (): void {
    $admin = User::factory()->create()->givePermissionTo('override-moderation');
    $game = Game::factory()->create(['status' => 'flagged']);
    $this->actingAs($admin);

    $response = $this->patch(route('admin.override.update', $game), [
        'status' => 'approved',
        'reason' => 'Looks good.',
    ]);

    $response->assertRedirect(route('admin.override.index'));
});

test('player cannot access the override index', function (): void {
    $player = User::factory()->create();
    $this->actingAs($player);

    $response = $this->get(route('admin.override.index'));

    $response->assertForbidden();
});

test('guest is redirected from the override index', function (): void {
    $response = $this->get(route('admin.override.index'));

    $response->assertRedirect(route('login'));
});

test('player cannot access the override show page', function (): void {
    $player = User::factory()->create();
    $game = Game::factory()->create(['status' => 'flagged']);
    $this->actingAs($player);

    $response = $this->get(route('admin.override.show', $game));

    $response->assertForbidden();
});

test('guest is redirected from override show page', function (): void {
    $game = Game::factory()->create(['status' => 'flagged']);

    $response = $this->get(route('admin.override.show', $game));

    $response->assertRedirect(route('login'));
});

test('override moderation record has is_override set to true', function (): void {
    $admin = User::factory()->create()->givePermissionTo('override-moderation');
    $game = Game::factory()->create(['status' => 'flagged']);
    $this->actingAs($admin);

    $this->patch(route('admin.override.update', $game), [
        'status' => 'approved',
        'reason' => 'Approved by admin.',
    ]);

    $moderation = GameModeration::query()
        ->where('game_id', $game->id)
        ->where('moderator_id', $admin->id)
        ->latest()
        ->first();

    expect($moderation)->not->toBeNull()
        ->and($moderation->is_override)->toBeTrue();
});
