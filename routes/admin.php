<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\CourtController;
use App\Http\Controllers\Admin\GameController;
use App\Http\Controllers\Admin\ModerationController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::resource('admin/courts', CourtController::class)
        ->names('admin.courts')
        ->except(['show']);

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

Route::middleware(['auth', 'verified', 'role:administrator'])->group(function (): void {
    Route::resource('admin/users', UserController::class)
        ->names('admin.users')
        ->only(['index', 'store', 'update', 'destroy']);
});

Route::middleware(['auth', 'verified', 'permission:moderate-games'])->group(function (): void {
    Route::get('admin/moderation', [ModerationController::class, 'index'])
        ->name('admin.moderation.index');
    Route::get('admin/moderation/{game}', [ModerationController::class, 'show'])
        ->name('admin.moderation.show');
    Route::patch('admin/moderation/{game}', [ModerationController::class, 'update'])
        ->name('admin.moderation.update');
});
