<?php

declare(strict_types=1);

use App\Enums\Role;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    foreach (Role::cases() as $role) {
        Spatie\Permission\Models\Role::query()->firstOrCreate(['name' => $role->value]);
    }
});
use App\Models\Country;
use App\Models\Profile;
use App\Models\User;

test('player without profile is redirected to profile settings when visiting dashboard', function (): void {
    $user = User::factory()->create();
    $user->assignRole(Role::Player);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertRedirect(route('profile.edit'));
});

test('player with profile can access the dashboard', function (): void {
    $user = User::factory()->create();
    $user->assignRole(Role::Player);
    Profile::factory()->create(['player_id' => $user->id]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
});

test('non-player user can access the dashboard without a profile', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
});

test('player can save their player profile', function (): void {
    $user = User::factory()->create();
    $user->assignRole(Role::Player);

    $country = Country::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('player-profile.update'), [
            'date_of_birth' => '1995-06-15',
            'country_id' => $country->id,
            'city' => 'Lagos',
            'phone_number' => '+2348012345678',
            'bio' => 'Passionate basketball player.',
            'position' => 'Point Guard',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    expect($user->profile()->exists())->toBeTrue();
});

test('player profile update requires all fields', function (): void {
    $user = User::factory()->create();
    $user->assignRole(Role::Player);

    $response = $this
        ->actingAs($user)
        ->from(route('profile.edit'))
        ->patch(route('player-profile.update'), []);

    $response->assertSessionHasErrors([
        'date_of_birth',
        'country_id',
        'city',
        'phone_number',
        'bio',
        'position',
    ]);
});

test('guest is redirected when trying to update player profile', function (): void {
    $response = $this->patch(route('player-profile.update'), []);

    $response->assertRedirect(route('login'));
});

test('player profile update sets session status to player-profile-updated', function (): void {
    $user = User::factory()->create();
    $user->assignRole(Role::Player);

    $country = Country::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('player-profile.update'), [
            'date_of_birth' => '1995-06-15',
            'country_id' => $country->id,
            'city' => 'Lagos',
            'phone_number' => '+2348012345678',
            'bio' => 'A player.',
            'position' => 'Center',
        ]);

    $response->assertSessionHas('status', 'player-profile-updated');
});

test('player profile image can be uploaded', function (): void {
    Storage::fake('public');

    $user = User::factory()->create();
    $user->assignRole(Role::Player);

    $country = Country::factory()->create();

    $this
        ->actingAs($user)
        ->patch(route('player-profile.update'), [
            'date_of_birth' => '1995-06-15',
            'country_id' => $country->id,
            'city' => 'Lagos',
            'phone_number' => '+2348012345678',
            'bio' => 'A player.',
            'position' => 'Center',
            'profile_image' => UploadedFile::fake()->image('avatar.jpg'),
        ]);

    expect($user->fresh()->profile->profile_image)->not->toBeNull();
    Storage::disk('public')->assertExists($user->fresh()->profile->profile_image);
});

test('player profile is updated when already exists', function (): void {
    $user = User::factory()->create();
    $user->assignRole(Role::Player);

    $country = Country::factory()->create();
    Profile::factory()->create(['player_id' => $user->id]);

    $this
        ->actingAs($user)
        ->patch(route('player-profile.update'), [
            'date_of_birth' => '1995-06-15',
            'country_id' => $country->id,
            'city' => 'Abuja',
            'phone_number' => '+2348099999999',
            'bio' => 'Updated bio.',
            'position' => 'Center',
        ]);

    expect($user->profile()->count())->toBe(1)
        ->and($user->profile->city)->toBe('Abuja');
});
