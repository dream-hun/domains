<?php

declare(strict_types=1);

use App\Actions\Admin\Allocation\GetAllocationSummary;
use App\Enums\Role;
use App\Models\Allocation;
use App\Models\AllocationConfiguration;
use App\Models\Game;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);

    AllocationConfiguration::query()->create([
        'insurance_percentage' => 25.0,
        'savings_percentage' => 25.0,
        'pathway_percentage' => 25.0,
        'administration_percentage' => 25.0,
    ]);
});

test('guest is redirected from allocation summary', function (): void {
    $this->get(route('admin.allocation.index'))
        ->assertRedirect(route('login'));
});

test('player cannot access allocation summary', function (): void {
    $player = User::factory()->create()->assignRole(Role::Player->value);
    $this->actingAs($player);

    $this->get(route('admin.allocation.index'))
        ->assertForbidden();
});

test('admin can view allocation summary page', function (): void {
    $admin = User::factory()->create()->assignRole(Role::Administrator->value);
    $this->actingAs($admin);

    $this->get(route('admin.allocation.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('admin/allocation/index')
            ->has('summary.total')
            ->has('summary.insurance')
            ->has('summary.savings')
            ->has('summary.pathway')
            ->has('summary.administration')
            ->has('summary.count')
        );
});

test('summary returns correct totals', function (): void {
    $config = AllocationConfiguration::query()->latest()->first();
    $game1 = Game::factory()->create();
    $game2 = Game::factory()->create();

    Allocation::query()->create([
        'game_id' => $game1->id,
        'player_id' => $game1->player_id,
        'total_amount' => 1.00,
        'insurance_amount' => 0.25,
        'savings_amount' => 0.25,
        'pathway_amount' => 0.25,
        'administration_amount' => 0.25,
        'allocation_configuration_id' => $config->id,
    ]);

    Allocation::query()->create([
        'game_id' => $game2->id,
        'player_id' => $game2->player_id,
        'total_amount' => 1.00,
        'insurance_amount' => 0.25,
        'savings_amount' => 0.25,
        'pathway_amount' => 0.25,
        'administration_amount' => 0.25,
        'allocation_configuration_id' => $config->id,
    ]);

    $action = resolve(GetAllocationSummary::class);
    $result = $action->handle();

    expect($result['count'])->toBe(2)
        ->and($result['total'])->toBe(2.0)
        ->and($result['insurance'])->toBe(0.5)
        ->and($result['savings'])->toBe(0.5)
        ->and($result['pathway'])->toBe(0.5)
        ->and($result['administration'])->toBe(0.5);
});

test('summary filters by player id', function (): void {
    $config = AllocationConfiguration::query()->latest()->first();
    $player1 = User::factory()->create();
    $player2 = User::factory()->create();
    $game1 = Game::factory()->create(['player_id' => $player1->id]);
    $game2 = Game::factory()->create(['player_id' => $player2->id]);

    foreach ([$game1, $game2] as $game) {
        Allocation::query()->create([
            'game_id' => $game->id,
            'player_id' => $game->player_id,
            'total_amount' => 1.00,
            'insurance_amount' => 0.25,
            'savings_amount' => 0.25,
            'pathway_amount' => 0.25,
            'administration_amount' => 0.25,
            'allocation_configuration_id' => $config->id,
        ]);
    }

    $action = resolve(GetAllocationSummary::class);
    $result = $action->handle(['player_id' => $player1->id]);

    expect($result['count'])->toBe(1)
        ->and($result['total'])->toBe(1.0);
});

test('summary filters by date range', function (): void {
    $config = AllocationConfiguration::query()->latest()->first();
    $game = Game::factory()->create();

    Allocation::query()->create([
        'game_id' => $game->id,
        'player_id' => $game->player_id,
        'total_amount' => 1.00,
        'insurance_amount' => 0.25,
        'savings_amount' => 0.25,
        'pathway_amount' => 0.25,
        'administration_amount' => 0.25,
        'allocation_configuration_id' => $config->id,
        'created_at' => now()->subDays(10),
    ]);

    $action = resolve(GetAllocationSummary::class);

    $resultWithin = $action->handle([
        'from' => now()->subDays(15)->toDateString(),
        'to' => now()->toDateString(),
    ]);

    $resultOutside = $action->handle([
        'from' => now()->subDays(5)->toDateString(),
        'to' => now()->toDateString(),
    ]);

    expect($resultWithin['count'])->toBe(1)
        ->and($resultOutside['count'])->toBe(0);
});

test('summary filters by game format', function (): void {
    $config = AllocationConfiguration::query()->latest()->first();
    $singlesGame = Game::factory()->create(['format' => 'singles']);
    $doublesGame = Game::factory()->create(['format' => 'doubles']);

    foreach ([$singlesGame, $doublesGame] as $game) {
        Allocation::query()->create([
            'game_id' => $game->id,
            'player_id' => $game->player_id,
            'total_amount' => 1.00,
            'insurance_amount' => 0.25,
            'savings_amount' => 0.25,
            'pathway_amount' => 0.25,
            'administration_amount' => 0.25,
            'allocation_configuration_id' => $config->id,
        ]);
    }

    $action = resolve(GetAllocationSummary::class);
    $result = $action->handle(['format' => 'singles']);

    expect($result['count'])->toBe(1);
});

test('csv export returns correct headers and data', function (): void {
    $admin = User::factory()->create()->assignRole(Role::Administrator->value);
    $this->actingAs($admin);

    $config = AllocationConfiguration::query()->latest()->first();
    $game = Game::factory()->create();

    Allocation::query()->create([
        'game_id' => $game->id,
        'player_id' => $game->player_id,
        'total_amount' => 1.00,
        'insurance_amount' => 0.25,
        'savings_amount' => 0.25,
        'pathway_amount' => 0.25,
        'administration_amount' => 0.25,
        'allocation_configuration_id' => $config->id,
    ]);

    $response = $this->get(route('admin.allocation.export'));

    $response->assertSuccessful();
    $response->assertHeader('Content-Type', 'text/csv; charset=utf-8');

    expect($response->getContent())->toContain('ID,Game ID,Player');
});

test('guest is redirected from csv export', function (): void {
    $this->get(route('admin.allocation.export'))
        ->assertRedirect(route('login'));
});

test('player cannot access csv export', function (): void {
    $player = User::factory()->create()->assignRole(Role::Player->value);
    $this->actingAs($player);

    $this->get(route('admin.allocation.export'))
        ->assertForbidden();
});

test('csv export has correct Content-Disposition header', function (): void {
    $admin = User::factory()->create()->assignRole(Role::Administrator->value);
    $this->actingAs($admin);

    $this->get(route('admin.allocation.export'))
        ->assertHeader('Content-Disposition', 'attachment; filename="allocations.csv"');
});

test('csv export includes data row with correct format', function (): void {
    $admin = User::factory()->create()->assignRole(Role::Administrator->value);
    $this->actingAs($admin);

    $config = AllocationConfiguration::query()->latest()->first();
    $game = Game::factory()->create(['format' => 'singles']);
    $player = User::find($game->player_id);

    $allocation = Allocation::query()->create([
        'game_id' => $game->id,
        'player_id' => $game->player_id,
        'total_amount' => 2.00,
        'insurance_amount' => 0.50,
        'savings_amount' => 0.50,
        'pathway_amount' => 0.50,
        'administration_amount' => 0.50,
        'allocation_configuration_id' => $config->id,
    ]);

    $response = $this->get(route('admin.allocation.export'));
    $content = $response->getContent();

    expect($content)
        ->toContain('"'.$player->name.'"')
        ->toContain('2.00')
        ->toContain('0.5000')
        ->toContain('"singles"')
        ->toContain($allocation->created_at->toDateString());
});

test('csv export filters by player id', function (): void {
    $admin = User::factory()->create()->assignRole(Role::Administrator->value);
    $this->actingAs($admin);

    $config = AllocationConfiguration::query()->latest()->first();
    $player1 = User::factory()->create();
    $player2 = User::factory()->create();
    $game1 = Game::factory()->create(['player_id' => $player1->id]);
    $game2 = Game::factory()->create(['player_id' => $player2->id]);

    foreach ([$game1, $game2] as $game) {
        Allocation::query()->create([
            'game_id' => $game->id,
            'player_id' => $game->player_id,
            'total_amount' => 1.00,
            'insurance_amount' => 0.25,
            'savings_amount' => 0.25,
            'pathway_amount' => 0.25,
            'administration_amount' => 0.25,
            'allocation_configuration_id' => $config->id,
        ]);
    }

    $response = $this->get(route('admin.allocation.export', ['player_id' => $player1->id]));
    $content = $response->getContent();

    expect($content)
        ->toContain('"'.$player1->name.'"')
        ->not->toContain('"'.$player2->name.'"');
});

test('csv export filters by date range', function (): void {
    $admin = User::factory()->create()->assignRole(Role::Administrator->value);
    $this->actingAs($admin);

    $config = AllocationConfiguration::query()->latest()->first();
    $game = Game::factory()->create();

    Allocation::query()->create([
        'game_id' => $game->id,
        'player_id' => $game->player_id,
        'total_amount' => 1.00,
        'insurance_amount' => 0.25,
        'savings_amount' => 0.25,
        'pathway_amount' => 0.25,
        'administration_amount' => 0.25,
        'allocation_configuration_id' => $config->id,
        'created_at' => now()->subDays(10),
    ]);

    $responseWithin = $this->get(route('admin.allocation.export', [
        'from' => now()->subDays(15)->toDateString(),
        'to' => now()->toDateString(),
    ]));

    $responseOutside = $this->get(route('admin.allocation.export', [
        'from' => now()->subDays(5)->toDateString(),
        'to' => now()->toDateString(),
    ]));

    $lines = fn (string $content) => count(array_filter(explode("\n", mb_trim($content)))) - 1;

    expect($lines($responseWithin->getContent()))->toBe(1)
        ->and($lines($responseOutside->getContent()))->toBe(0);
});

test('csv export filters by game format', function (): void {
    $admin = User::factory()->create()->assignRole(Role::Administrator->value);
    $this->actingAs($admin);

    $config = AllocationConfiguration::query()->latest()->first();
    $singlesGame = Game::factory()->create(['format' => 'singles']);
    $doublesGame = Game::factory()->create(['format' => 'doubles']);

    foreach ([$singlesGame, $doublesGame] as $game) {
        Allocation::query()->create([
            'game_id' => $game->id,
            'player_id' => $game->player_id,
            'total_amount' => 1.00,
            'insurance_amount' => 0.25,
            'savings_amount' => 0.25,
            'pathway_amount' => 0.25,
            'administration_amount' => 0.25,
            'allocation_configuration_id' => $config->id,
        ]);
    }

    $response = $this->get(route('admin.allocation.export', ['format' => 'singles']));
    $content = $response->getContent();

    expect($content)
        ->toContain('"singles"')
        ->not->toContain('"doubles"');
});

test('allocation index passes filters in page props', function (): void {
    $admin = User::factory()->create()->assignRole(Role::Administrator->value);
    $this->actingAs($admin);

    $this->get(route('admin.allocation.index', ['from' => '2026-01-01', 'format' => 'singles']))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('filters.from', '2026-01-01')
            ->where('filters.format', 'singles')
        );
});
