<?php

declare(strict_types=1);

use App\Models\Country;
use App\Models\Profile;
use App\Models\User;

test('profile belongs to a player', function (): void {
    $user = User::factory()->create();
    $profile = Profile::factory()->create(['player_id' => $user->id]);

    expect($profile->player)->toBeInstanceOf(User::class)
        ->and($profile->player->id)->toBe($user->id);
});

test('profile belongs to a country', function (): void {
    $country = Country::factory()->create();
    $profile = Profile::factory()->create(['country_id' => $country->id]);

    expect($profile->country)->toBeInstanceOf(Country::class)
        ->and($profile->country->id)->toBe($country->id);
});

test('profile uuid is auto generated on creation', function (): void {
    $profile = Profile::factory()->create();

    expect($profile->uuid)->not->toBeNull()
        ->and($profile->uuid)->toBeString();
});

test('profile unique ids returns uuid column', function (): void {
    $profile = Profile::factory()->create();

    expect($profile->uniqueIds())->toBe(['uuid']);
});

test('each profile has a distinct uuid', function (): void {
    $first = Profile::factory()->create();
    $second = Profile::factory()->create();

    expect($first->uuid)->not->toBe($second->uuid);
});
