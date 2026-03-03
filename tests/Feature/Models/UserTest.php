<?php

declare(strict_types=1);

use App\Models\PlayerRanking;
use App\Models\Profile;
use App\Models\RankingConfiguration;
use App\Models\User;

test('user has one profile', function (): void {
    $user = User::factory()->create();
    Profile::factory()->create(['player_id' => $user->id]);

    expect($user->profile)->toBeInstanceOf(Profile::class)
        ->and($user->profile->player_id)->toBe($user->id);
});

test('user profile is null when no profile exists', function (): void {
    $user = User::factory()->create();

    expect($user->profile)->toBeNull();
});

test('user has many rankings', function (): void {
    $user = User::factory()->create();
    $config = RankingConfiguration::query()->create([
        'win_weight' => 3.0,
        'loss_weight' => 1.0,
        'game_count_weight' => 0.5,
        'frequency_weight' => 2.0,
    ]);

    PlayerRanking::query()->create([
        'player_id' => $user->id,
        'ranking_configuration_id' => $config->id,
        'format' => '1v1',
        'wins' => 2,
        'losses' => 1,
        'total_games' => 3,
        'recent_games' => 2,
        'score' => 10.5,
        'rank' => 1,
        'calculated_at' => now(),
    ]);

    expect($user->rankings)->toHaveCount(1)
        ->and($user->rankings->first())->toBeInstanceOf(PlayerRanking::class);
});

test('user uuid is auto generated on creation', function (): void {
    $user = User::factory()->create();

    expect($user->uuid)->not->toBeNull()
        ->and($user->uuid)->toBeString();
});

test('user unique ids returns uuid column', function (): void {
    $user = User::factory()->create();

    expect($user->uniqueIds())->toBe(['uuid']);
});

test('user password is hidden from array serialization', function (): void {
    $user = User::factory()->create();
    $array = $user->toArray();

    expect($array)->not->toHaveKey('password')
        ->not->toHaveKey('two_factor_secret')
        ->not->toHaveKey('two_factor_recovery_codes')
        ->not->toHaveKey('remember_token');
});

test('unverified factory state sets email verified at to null', function (): void {
    $user = User::factory()->unverified()->create();

    expect($user->email_verified_at)->toBeNull();
});

test('with two factor factory state sets two factor fields', function (): void {
    $user = User::factory()->withTwoFactor()->create();

    expect($user->two_factor_secret)->not->toBeNull()
        ->and($user->two_factor_recovery_codes)->not->toBeNull()
        ->and($user->two_factor_confirmed_at)->not->toBeNull();
});
