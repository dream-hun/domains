<?php

declare(strict_types=1);

use App\Models\User;
use Inertia\Testing\AssertableInertia;

test('guests are redirected to the login page', function (): void {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->component('dashboard')
        ->has('stats')
        ->has('stats.total_games')
        ->has('stats.total_courts')
        ->has('stats.pending_games')
        ->has('stats.approved_games')
        ->has('recent_games')
        ->has('games_per_month')
    );
});
