<?php

declare(strict_types=1);

use App\Models\Game;
use App\Models\User;
use Vimeo\Laravel\Facades\Vimeo;

test('guests are redirected from games index', function (): void {
    $response = $this->get(route('admin.games.index'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can view games index', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('admin.games.index'));
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('admin/games/index'));
});

test('authenticated users can create a game', function (): void {
    $user = User::factory()->create();
    $player = User::factory()->create();
    $this->actingAs($user);

    $response = $this->post(route('admin.games.store'), [
        'title' => 'Test Game',
        'format' => '5v5',
        'court_id' => null,
        'player_id' => $player->id,
        'played_at' => '2026-01-15 10:00:00',
    ]);

    $response->assertRedirect(route('admin.games.index'));

    $this->assertDatabaseHas('games', [
        'title' => 'Test Game',
        'format' => '5v5',
        'player_id' => $player->id,
    ]);
});

test('create game validates required fields', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->post(route('admin.games.store'), []);

    $response->assertInvalid(['title', 'format', 'player_id', 'played_at']);
});

test('authenticated users can update a game', function (): void {
    $user = User::factory()->create();
    $player = User::factory()->create();
    $game = Game::factory()->create(['player_id' => $player->id]);
    $this->actingAs($user);

    $newPlayer = User::factory()->create();

    $response = $this->patch(route('admin.games.update', $game), [
        'title' => 'Updated Game',
        'format' => '3v3',
        'court_id' => null,
        'player_id' => $newPlayer->id,
        'played_at' => '2026-02-01 14:00:00',
    ]);

    $response->assertRedirect(route('admin.games.index'));

    $this->assertDatabaseHas('games', [
        'id' => $game->id,
        'title' => 'Updated Game',
        'format' => '3v3',
        'player_id' => $newPlayer->id,
    ]);
});

test('authenticated users can delete a game', function (): void {
    $user = User::factory()->create();
    $game = Game::factory()->create();
    $this->actingAs($user);

    $response = $this->delete(route('admin.games.destroy', $game));

    $response->assertRedirect();

    $this->assertDatabaseMissing('games', [
        'id' => $game->id,
    ]);
});

test('initiate upload returns json with upload link', function (): void {
    $user = User::factory()->create();
    $game = Game::factory()->create(['title' => 'My Game']);
    $this->actingAs($user);

    Vimeo::shouldReceive('request')
        ->once()
        ->with('/me/videos', [
            'upload' => [
                'approach' => 'tus',
                'size' => 1024000,
            ],
            'name' => 'My Game',
        ], 'POST')
        ->andReturn([
            'body' => [
                'uri' => '/videos/123456789',
                'upload' => [
                    'upload_link' => 'https://asia-files.tus.vimeo.com/files/123456789',
                ],
            ],
        ]);

    $response = $this->postJson(route('admin.games.upload-url', $game), [
        'file_size' => 1024000,
        'file_name' => 'game-video.mp4',
    ]);

    $response->assertOk();
    $response->assertJson(['upload_link' => 'https://asia-files.tus.vimeo.com/files/123456789']);

    $this->assertDatabaseHas('games', [
        'id' => $game->id,
        'vimeo_uri' => '/videos/123456789',
        'vimeo_status' => 'pending',
    ]);
});

test('complete upload sets vimeo status to complete', function (): void {
    $user = User::factory()->create();
    $game = Game::factory()->create([
        'vimeo_uri' => '/videos/123456789',
        'vimeo_status' => 'pending',
    ]);
    $this->actingAs($user);

    $response = $this->patch(route('admin.games.complete-upload', $game));

    $response->assertRedirect(route('admin.games.index'));

    $this->assertDatabaseHas('games', [
        'id' => $game->id,
        'vimeo_status' => 'complete',
    ]);
});
