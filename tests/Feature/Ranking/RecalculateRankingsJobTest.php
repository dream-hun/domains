<?php

declare(strict_types=1);

use App\Actions\Ranking\CalculateRankingsAction;
use App\Jobs\RecalculateRankingsJob;
use App\Models\Game;
use App\Models\PlayerRanking;
use App\Models\RankingConfiguration;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Permission;

beforeEach(function (): void {
    Permission::query()->firstOrCreate(['name' => 'moderate-games']);

    RankingConfiguration::query()->create([
        'win_weight' => 3.0,
        'loss_weight' => 1.0,
        'game_count_weight' => 0.5,
        'frequency_weight' => 2.0,
    ]);
});

test('job is dispatched when a game is approved via moderation', function (): void {
    Queue::fake();

    $moderator = User::factory()->create()->givePermissionTo('moderate-games');
    $game = Game::factory()->create(['status' => 'pending']);
    $this->actingAs($moderator);

    $this->patch(route('admin.moderation.update', $game), [
        'status' => 'approved',
        'reason' => 'Looks good.',
    ]);

    Queue::assertPushed(RecalculateRankingsJob::class);
});

test('job is not dispatched when a game is rejected', function (): void {
    Queue::fake();

    $moderator = User::factory()->create()->givePermissionTo('moderate-games');
    $game = Game::factory()->create(['status' => 'pending']);
    $this->actingAs($moderator);

    $this->patch(route('admin.moderation.update', $game), [
        'status' => 'rejected',
        'reason' => 'Poor quality.',
    ]);

    Queue::assertNotPushed(RecalculateRankingsJob::class);
});

test('job is not dispatched when a game is flagged', function (): void {
    Queue::fake();

    $moderator = User::factory()->create()->givePermissionTo('moderate-games');
    $game = Game::factory()->create(['status' => 'pending']);
    $this->actingAs($moderator);

    $this->patch(route('admin.moderation.update', $game), [
        'status' => 'flagged',
        'reason' => 'Needs review.',
    ]);

    Queue::assertNotPushed(RecalculateRankingsJob::class);
});

test('job runs the action and creates ranking snapshots for the given config', function (): void {
    $config = RankingConfiguration::query()->latest()->first();
    $player = User::factory()->create();
    Game::factory()->create(['player_id' => $player->id, 'status' => 'approved', 'result' => 'win', 'format' => '1v1']);

    $job = new RecalculateRankingsJob($config->id);
    $job->handle(new CalculateRankingsAction());

    expect(PlayerRanking::query()->where('ranking_configuration_id', $config->id)->exists())->toBeTrue();
});
