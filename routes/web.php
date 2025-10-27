<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\BillingController;
use App\Http\Controllers\Admin\ContactController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DomainController;
use App\Http\Controllers\Admin\DomainOperationsController;
use App\Http\Controllers\Admin\DomainPriceController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\PermissionsController;
use App\Http\Controllers\Admin\RolesController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UsersController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckOutController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RegisterDomainController;
use App\Http\Controllers\SearchDomainController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingController::class)->name('home');

// Temporary route for testing - remove after testing
Route::get('/clear-currency', function () {
    session()->forget('selected_currency');

    return redirect('/')->with('success', 'Currency session cleared. Please refresh.');
})->name('clear.currency');

Route::get('/shopping-cart', CartController::class)->name('cart.index');
Route::get('/domains', [SearchDomainController::class, 'index'])->name('domains');
Route::post('/domains/search', [SearchDomainController::class, 'search'])->name('domains.search');

// Currency routes
Route::get('/api/currencies', [CurrencyController::class, 'index'])->name('currencies.index');
Route::post('/api/currencies/switch', [CurrencyController::class, 'switch'])->name('currencies.switch');

Route::get('/dashboard', DashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');

Route::group(['middleware' => ['auth', 'verified'], 'prefix' => 'admin', 'as' => 'admin.'], function (): void {
    Route::resource('contacts', ContactController::class);

    Route::resource('domains', DomainController::class)->except(['show']);
    Route::get('domains/{domain:uuid}/info', [DomainOperationsController::class, 'domainInfo'])->name('domain.info');
    Route::post('/domains/{domain:uuid}/fetch-contacts', [DomainOperationsController::class, 'getContacts'])->name('domain.fetchContacts');
    Route::get('domains/{domain:uuid}/contacts/{type}/edit', [DomainController::class, 'editContact'])->name('domains.contacts.edit');
    Route::put('domains/{domain:uuid}/contacts', [DomainController::class, 'updateContacts'])->name('domains.contacts.update');
    Route::put('domains/{domain:uuid}/lock', [DomainController::class, 'toggleLock'])->name('domains.lock');
    Route::get('domains/{domain:uuid}/transfer', [DomainController::class, 'showTransferForm'])->name('domains.transfer');
    Route::post('domains/{domain:uuid}/transfer', [DomainController::class, 'transferDomain'])->name('domains.transfer.store');
    Route::get('domains/{domain:uuid}/renew', [DomainController::class, 'showRenewForm'])->name('domains.renew');
    Route::post('domains/{domain:uuid}/renew', [DomainController::class, 'renewDomain'])->name('domains.renew.store');
    Route::post('domains/{domain:uuid}/reactivate', [DomainController::class, 'reactivate'])->name('domains.reactivate');
    Route::get('domains/{domain:uuid}/nameservers', [DomainController::class, 'edit'])->name('domains.edit');
    Route::put('domains/{domain:uuid}/nameservers', [DomainController::class, 'updateNameservers'])->name('domains.nameservers.update');

    Route::resource('permissions', PermissionsController::class);
    Route::resource('roles', RolesController::class);
    Route::resource('users', UsersController::class);
    Route::resource('prices', DomainPriceController::class)->except(['show']);
    Route::resource('settings', SettingController::class);

    // Notification routes
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/{id}/mark-read', [NotificationController::class, 'markAsRead'])->name('notifications.mark-read');
    Route::post('notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
    Route::delete('notifications/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
    Route::delete('notifications', [NotificationController::class, 'destroyAll'])->name('notifications.destroy-all');

});

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('/cart/checkout/payment/', [CheckOutController::class, 'index'])->name('checkout.index');
    Route::get('/checkout/stripe/redirect/{order}', [CheckOutController::class, 'stripeRedirect'])->name('checkout.stripe.redirect');
    Route::get('/checkout/stripe/success/{order}', [CheckOutController::class, 'stripeSuccess'])->name('checkout.stripe.success');
    Route::get('/checkout/stripe/cancel/{order}', [CheckOutController::class, 'stripeCancel'])->name('checkout.stripe.cancel');
    Route::get('/domains/register', [RegisterDomainController::class, 'index'])->name('domains.register');
    Route::post('/domains/register', [RegisterDomainController::class, 'register'])->name('domains.register.store');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Billing
    Route::get('/billing', [BillingController::class, 'index'])->name('billing.index');

    Route::get('/contacts/{id}/details', [App\Http\Controllers\Api\ContactController::class, 'details'])->name('contacts.details');
    Route::get('/api/contacts/{id}', [App\Http\Controllers\Api\ContactController::class, 'details'])->name('api.contacts.details');

    // Payment routes
    Route::get('/payment', [PaymentController::class, 'showPaymentPage'])->name('payment.index');
    Route::post('/payment/stripe', [PaymentController::class, 'processStripePayment'])->name('payment.stripe');
    Route::get('/payment/success/{order}', [PaymentController::class, 'handlePaymentSuccess'])->name('payment.success');
    Route::get('/payment/success/{order}/show', [PaymentController::class, 'showPaymentSuccess'])->name('payment.success.show');
    Route::get('/payment/cancel/{order}', [PaymentController::class, 'handlePaymentCancel'])->name('payment.cancel');
    Route::get('/payment/failed/{order}', [PaymentController::class, 'showPaymentFailed'])->name('payment.failed');

});

// Stripe webhook route (no auth required)
Route::post('/stripe/webhook', [App\Http\Controllers\StripeWebhookController::class, 'handleWebhook'])->name('stripe.webhook');

require __DIR__.'/auth.php';
