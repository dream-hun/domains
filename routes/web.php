<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\ContactController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DomainController;
use App\Http\Controllers\Admin\PermissionsController;
use App\Http\Controllers\Admin\RolesController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UsersController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RegisterDomainController;
use App\Http\Controllers\SearchDomainController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingController::class)->name('home');
Route::get('/shopping-cart', CartController::class)->name('cart.index');
Route::get('/domains', [SearchDomainController::class, 'index'])->name('domains');
Route::post('/domains/search', [SearchDomainController::class, 'search'])->name('domains.search');

Route::get('/dashboard', DashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');

Route::group(['middleware' => ['auth', 'verified'], 'prefix' => 'admin', 'as' => 'admin.'], function (): void {
    Route::resource('contacts', ContactController::class);

    Route::resource('domains', DomainController::class)->except(['show']);
    Route::get('domains/{domain:uuid}/info', [DomainController::class, 'domainInfo'])->name('domains.info');
    Route::post('domains/{domain:uuid}/refresh-info', [DomainController::class, 'refreshDomainInfo'])->name('domains.refresh-info');
    Route::post('domains/{domain:uuid}/contacts', [DomainController::class, 'updateContacts'])->name('domains.update-contacts');
    Route::put('domains/{domain:uuid}/lock', [DomainController::class, 'toggleLock'])->name('domains.lock');
    Route::get('domains/{domain:uuid}/transfer', [DomainController::class, 'showTransferForm'])->name('domains.transfer');
    Route::post('domains/{domain:uuid}/transfer', [DomainController::class, 'transferDomain'])->name('domains.transfer.store');
    Route::get('domains/{domain:uuid}/renew', [DomainController::class, 'showRenewForm'])->name('domains.renew');
    Route::post('domains/{domain:uuid}/renew', [DomainController::class, 'renewDomain'])->name('domains.renew.store');
    Route::get('domains/{domain:uuid}/nameservers', [DomainController::class, 'showNameserversForm'])->name('domains.nameservers.edit');
    Route::put('domains/{domain:uuid}/nameservers', [DomainController::class, 'updateNameservers'])->name('domains.nameservers.update');

    Route::resource('permissions', PermissionsController::class);
    Route::resource('roles', RolesController::class);
    Route::resource('users', UsersController::class);
    Route::resource('settings', SettingController::class);

});

Route::middleware('auth')->group(function (): void {
    Route::get('/domains/register', [RegisterDomainController::class, 'index'])->name('domains.register');
    Route::post('/domains/register', [RegisterDomainController::class, 'register'])->name('domains.register.store');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/contacts/{id}/details', [App\Http\Controllers\Api\ContactController::class, 'details'])->name('contacts.details');
    Route::get('/api/contacts/{id}', [App\Http\Controllers\Api\ContactController::class, 'details'])->name('api.contacts.details');
});

require __DIR__.'/auth.php';
