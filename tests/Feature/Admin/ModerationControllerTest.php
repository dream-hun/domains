<?php

declare(strict_types=1);

use App\Models\Game;
use App\Models\GameModeration;
use App\Models\User;
use Spatie\Permission\Models\Permission;

beforeEach(function (): void {
    Permission::query()->firstOrCreate(['name' => 'moderate-games']);
});

test('moderator can view the moderation queue', function (): void {
    $moderator = User::factory()->create()->givePermissionTo('moderate-games');
    $this->actingAs($moderator);

    Game::factory()->create(['status' => 'pending']);

    $response = $this->get(route('admin.moderation.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('admin/moderation/index'));
});

test('moderation queue only shows pending games', function (): void {
    $moderator = User::factory()->create()->givePermissionTo('moderate-games');
    $this->actingAs($moderator);

    $pending = Game::factory()->create(['status' => 'pending']);
    Game::factory()->create(['status' => 'approved']);

    $response = $this->get(route('admin.moderation.index'));

    $response->assertOk();
    $response->assertInertia(
        fn ($page) => $page
            ->component('admin/moderation/index')
            ->has('games.data', 1)
            ->where('games.data.0.id', $pending->id)
    );
});

test('moderator can approve a game', function (): void {
    $moderator = User::factory()->create()->givePermissionTo('moderate-games');
    $game = Game::factory()->create(['status' => 'pending']);
    $this->actingAs($moderator);

    $response = $this->patch(route('admin.moderation.update', $game), [
        'status' => 'approved',
        'reason' => 'Video meets all quality standards.',
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('game_moderations', [
        'game_id' => $game->id,
        'moderator_id' => $moderator->id,
        'status' => 'approved',
        'reason' => 'Video meets all quality standards.',
    ]);

    $this->assertDatabaseHas('games', [
        'id' => $game->id,
        'status' => 'approved',
    ]);
});

test('moderator can reject a game with a reason', function (): void {
    $moderator = User::factory()->create()->givePermissionTo('moderate-games');
    $game = Game::factory()->create(['status' => 'pending']);
    $this->actingAs($moderator);

    $response = $this->patch(route('admin.moderation.update', $game), [
        'status' => 'rejected',
        'reason' => 'Video quality is too poor.',
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('game_moderations', [
        'game_id' => $game->id,
        'moderator_id' => $moderator->id,
        'status' => 'rejected',
        'reason' => 'Video quality is too poor.',
    ]);

    $this->assertDatabaseHas('games', [
        'id' => $game->id,
        'status' => 'rejected',
    ]);
});

test('moderator can flag a game', function (): void {
    $moderator = User::factory()->create()->givePermissionTo('moderate-games');
    $game = Game::factory()->create(['status' => 'pending']);
    $this->actingAs($moderator);

    $response = $this->patch(route('admin.moderation.update', $game), [
        'status' => 'flagged',
        'reason' => 'Needs further review from senior moderator.',
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('game_moderations', [
        'game_id' => $game->id,
        'moderator_id' => $moderator->id,
        'status' => 'flagged',
    ]);

    $this->assertDatabaseHas('games', [
        'id' => $game->id,
        'status' => 'flagged',
    ]);
});

test('reason is required for moderation decision', function (): void {
    $moderator = User::factory()->create()->givePermissionTo('moderate-games');
    $game = Game::factory()->create(['status' => 'pending']);
    $this->actingAs($moderator);

    $response = $this->patch(route('admin.moderation.update', $game), [
        'status' => 'approved',
    ]);

    $response->assertInvalid(['reason']);
});

test('status is required for moderation decision', function (): void {
    $moderator = User::factory()->create()->givePermissionTo('moderate-games');
    $game = Game::factory()->create(['status' => 'pending']);
    $this->actingAs($moderator);

    $response = $this->patch(route('admin.moderation.update', $game), [
        'reason' => 'Some reason.',
    ]);

    $response->assertInvalid(['status']);
});

test('player cannot access the moderation queue', function (): void {
    $player = User::factory()->create();
    $this->actingAs($player);

    $response = $this->get(route('admin.moderation.index'));

    $response->assertForbidden();
});

test('guest is redirected from moderation queue', function (): void {
    $response = $this->get(route('admin.moderation.index'));

    $response->assertRedirect(route('login'));
});

test('moderator can view the moderation show page', function (): void {
    $moderator = User::factory()->create()->givePermissionTo('moderate-games');
    $game = Game::factory()->create(['status' => 'pending']);
    $this->actingAs($moderator);

    $response = $this->get(route('admin.moderation.show', $game));

    $response->assertOk();
    $response->assertInertia(
        fn ($page) => $page
            ->component('admin/moderation/show')
            ->where('game.id', $game->id)
            ->has('game.player')
            ->has('game.court')
    );
});

test('show page returns 404 for non-existent game', function (): void {
    $moderator = User::factory()->create()->givePermissionTo('moderate-games');
    $this->actingAs($moderator);

    $response = $this->get(route('admin.moderation.show', 99999));

    $response->assertNotFound();
});

test('player cannot access moderation show page', function (): void {
    $player = User::factory()->create();
    $game = Game::factory()->create(['status' => 'pending']);
    $this->actingAs($player);

    $response = $this->get(route('admin.moderation.show', $game));

    $response->assertForbidden();
});

test('guest is redirected from moderation show page', function (): void {
    $game = Game::factory()->create(['status' => 'pending']);

    $response = $this->get(route('admin.moderation.show', $game));

    $response->assertRedirect(route('login'));
});

test('update redirects to moderation index on success', function (): void {
    $moderator = User::factory()->create()->givePermissionTo('moderate-games');
    $game = Game::factory()->create(['status' => 'pending']);
    $this->actingAs($moderator);

    $response = $this->patch(route('admin.moderation.update', $game), [
        'status' => 'approved',
        'reason' => 'Video meets all quality standards.',
    ]);

    $response->assertRedirect(route('admin.moderation.index'));
});

test('game moderation belongs to a game', function (): void {
    $moderator = User::factory()->create()->givePermissionTo('moderate-games');
    $game = Game::factory()->create(['status' => 'pending']);

    $moderation = GameModeration::query()->create([
        'game_id' => $game->id,
        'moderator_id' => $moderator->id,
        'status' => 'approved',
        'reason' => 'Looks good.',
    ]);

    expect($moderation->game)->toBeInstanceOf(Game::class)
        ->and($moderation->game->id)->toBe($game->id);
});

test('game moderation belongs to a moderator', function (): void {
    $moderator = User::factory()->create()->givePermissionTo('moderate-games');
    $game = Game::factory()->create(['status' => 'pending']);

    $moderation = GameModeration::query()->create([
        'game_id' => $game->id,
        'moderator_id' => $moderator->id,
        'status' => 'rejected',
        'reason' => 'Poor quality.',
    ]);

    expect($moderation->moderator)->toBeInstanceOf(User::class)
        ->and($moderation->moderator->id)->toBe($moderator->id);
});
