<?php

declare(strict_types=1);

use App\Actions\Admin\Allocation\CreateAllocation;
use App\Jobs\CreateGameAllocationJob;
use App\Models\Allocation;
use App\Models\AllocationConfiguration;
use App\Models\Game;
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

    AllocationConfiguration::query()->create([
        'insurance_percentage' => 25.0,
        'savings_percentage' => 25.0,
        'pathway_percentage' => 25.0,
        'administration_percentage' => 25.0,
    ]);
});

test('create allocation action creates record with correct amounts', function (): void {
    $game = Game::factory()->create(['status' => 'approved']);
    $action = resolve(CreateAllocation::class);

    $allocation = $action->handle($game);

    expect($allocation)->toBeInstanceOf(Allocation::class)
        ->and($allocation->game_id)->toBe($game->id)
        ->and($allocation->player_id)->toBe($game->player_id)
        ->and($allocation->total_amount)->toBe(1.0)
        ->and($allocation->insurance_amount)->toBe(0.25)
        ->and($allocation->savings_amount)->toBe(0.25)
        ->and($allocation->pathway_amount)->toBe(0.25)
        ->and($allocation->administration_amount)->toBe(0.25);
});

test('allocation amounts sum to total amount', function (): void {
    $game = Game::factory()->create(['status' => 'approved']);
    $action = resolve(CreateAllocation::class);

    $allocation = $action->handle($game);

    $sum = $allocation->insurance_amount
        + $allocation->savings_amount
        + $allocation->pathway_amount
        + $allocation->administration_amount;

    expect(abs($sum - $allocation->total_amount))->toBeLessThan(0.001);
});

test('allocation stores reference to configuration', function (): void {
    $game = Game::factory()->create(['status' => 'approved']);
    $action = resolve(CreateAllocation::class);

    $allocation = $action->handle($game);
    $config = AllocationConfiguration::query()->latest('id')->first();

    expect($allocation->allocation_configuration_id)->toBe($config->id);
});

test('allocation is created with non-uniform percentages', function (): void {
    AllocationConfiguration::query()->create([
        'insurance_percentage' => 40.0,
        'savings_percentage' => 30.0,
        'pathway_percentage' => 20.0,
        'administration_percentage' => 10.0,
    ]);

    $game = Game::factory()->create(['status' => 'approved']);
    $action = resolve(CreateAllocation::class);

    $allocation = $action->handle($game);

    expect($allocation->insurance_amount)->toBe(0.4)
        ->and($allocation->savings_amount)->toBe(0.3)
        ->and($allocation->pathway_amount)->toBe(0.2)
        ->and($allocation->administration_amount)->toBe(0.1);
});

test('approving game via moderation dispatches allocation job', function (): void {
    Queue::fake();

    $moderator = User::factory()->create()->givePermissionTo('moderate-games');
    $game = Game::factory()->create(['status' => 'pending']);
    $this->actingAs($moderator);

    $this->patch(route('admin.moderation.update', $game), [
        'status' => 'approved',
        'reason' => 'Looks good.',
    ]);

    Queue::assertPushed(CreateGameAllocationJob::class, fn ($job): bool => $job->gameId === $game->id);
});

test('rejecting game via moderation does not dispatch allocation job', function (): void {
    Queue::fake();

    $moderator = User::factory()->create()->givePermissionTo('moderate-games');
    $game = Game::factory()->create(['status' => 'pending']);
    $this->actingAs($moderator);

    $this->patch(route('admin.moderation.update', $game), [
        'status' => 'rejected',
        'reason' => 'Poor quality.',
    ]);

    Queue::assertNotPushed(CreateGameAllocationJob::class);
});

test('allocation job creates allocation record', function (): void {
    $game = Game::factory()->create(['status' => 'approved']);

    dispatch(new CreateGameAllocationJob($game->id));

    $this->assertDatabaseHas('allocations', [
        'game_id' => $game->id,
        'player_id' => $game->player_id,
    ]);
});
