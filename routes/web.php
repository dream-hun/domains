<?php

declare(strict_types=1);

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LeaderboardController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', fn () => Inertia::render('auth/login', [
    'canRegister' => Features::enabled(Features::registration()),
]))->name('home');

Route::get('dashboard', DashboardController::class)->middleware(['auth', 'verified', 'player.profile'])->name('dashboard');
Route::get('leaderboard', LeaderboardController::class)->middleware(['auth', 'verified', 'player.profile'])->name('leaderboard');

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';
