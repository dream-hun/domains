<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AllocationConfigurationController;
use App\Http\Controllers\Admin\AllocationController;
use App\Http\Controllers\Admin\CourtController;
use App\Http\Controllers\Admin\GameController;
use App\Http\Controllers\Admin\ModerationController;
use App\Http\Controllers\Admin\OverrideController;
use App\Http\Controllers\Admin\RankingConfigurationController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'permission:edit-courts'])->group(function (): void {
    Route::resource('admin/courts', CourtController::class)
        ->names('admin.courts')
        ->except(['show']);
});

Route::middleware(['auth', 'verified', 'permission:view-games'])->group(function (): void {
    Route::resource('admin/games', GameController::class)
        ->names('admin.games')
        ->except(['show']);

    Route::get('admin/games/{game}/upload', [GameController::class, 'showUpload'])
        ->name('admin.games.upload');
    Route::post('admin/games/{game}/upload-url', [GameController::class, 'initiateUpload'])
        ->name('admin.games.upload-url');
    Route::patch('admin/games/{game}/complete-upload', [GameController::class, 'completeUpload'])
        ->name('admin.games.complete-upload');
});

Route::middleware(['auth', 'verified', 'permission:manage-users'])->group(function (): void {
    Route::resource('admin/users', UserController::class)
        ->names('admin.users')
        ->only(['index', 'store', 'update', 'destroy']);
});

Route::middleware(['auth', 'verified', 'permission:manage-ranking-configuration'])->group(function (): void {
    Route::get('admin/ranking', [RankingConfigurationController::class, 'edit'])->name('admin.ranking.edit');
    Route::post('admin/ranking', [RankingConfigurationController::class, 'update'])->name('admin.ranking.update');
});

Route::middleware(['auth', 'verified', 'permission:moderate-games'])->group(function (): void {
    Route::get('admin/moderation', [ModerationController::class, 'index'])
        ->name('admin.moderation.index');
    Route::get('admin/moderation/{game}', [ModerationController::class, 'show'])
        ->name('admin.moderation.show');
    Route::patch('admin/moderation/{game}', [ModerationController::class, 'update'])
        ->name('admin.moderation.update');
});

Route::middleware(['auth', 'verified', 'permission:override-moderation'])->group(function (): void {
    Route::get('admin/override', [OverrideController::class, 'index'])
        ->name('admin.override.index');
    Route::get('admin/override/{game}', [OverrideController::class, 'show'])
        ->name('admin.override.show');
    Route::patch('admin/override/{game}', [OverrideController::class, 'update'])
        ->name('admin.override.update');
});

Route::middleware(['auth', 'verified', 'permission:view-allocations'])->group(function (): void {
    Route::get('admin/allocation', [AllocationController::class, 'index'])->name('admin.allocation.index');
    Route::get('admin/allocation/export', [AllocationController::class, 'export'])->name('admin.allocation.export');
});

Route::middleware(['auth', 'verified', 'permission:manage-allocation-configuration'])->group(function (): void {
    Route::get('admin/allocation-configuration', [AllocationConfigurationController::class, 'edit'])->name('admin.allocation-configuration.edit');
    Route::patch('admin/allocation-configuration', [AllocationConfigurationController::class, 'update'])->name('admin.allocation-configuration.update');
});
