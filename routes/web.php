<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\BillingController;
use App\Http\Controllers\Admin\ContactController;
use App\Http\Controllers\Admin\CurrencyController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DomainController;
use App\Http\Controllers\Admin\DomainOperationsController;
use App\Http\Controllers\Admin\DomainPriceHistoryController;
use App\Http\Controllers\Admin\FailedDomainRegistrationController;
use App\Http\Controllers\Admin\FeatureCategoryController;
use App\Http\Controllers\Admin\HostingCategoryController;
use App\Http\Controllers\Admin\HostingFeatureController;
use App\Http\Controllers\Admin\HostingPlanController;
use App\Http\Controllers\Admin\HostingPlanFeatureController;
use App\Http\Controllers\Admin\HostingPlanPriceController;
use App\Http\Controllers\Admin\HostingPlanPriceHistoryController;
use App\Http\Controllers\Admin\HostingPromotionController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\PermissionsController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\RolesController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\SubscriptionController;
use App\Http\Controllers\Admin\TldController;
use App\Http\Controllers\Admin\TldPricingController;
use App\Http\Controllers\Admin\UsersController;
use App\Http\Controllers\Admin\VpsController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryShowController;
use App\Http\Controllers\CheckoutController as RenewalCheckoutController;
use App\Http\Controllers\DomainRenewalController;
use App\Http\Controllers\KpayPaymentController;
use App\Http\Controllers\KPayWebhookController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RegisterDomainController;
use App\Http\Controllers\SearchDomainController;
use App\Http\Controllers\SmartCheckoutController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\SubscriptionRenewalController;
use App\Http\Controllers\User\VpsController as UserVpsController;
use App\Livewire\Hosting\Configuration;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingController::class)->name('home');
Route::get('/hosting/purchase/{plan}', Configuration::class)->name('hosting.configure');
Route::get('/hosting/{slug}', CategoryShowController::class)->name('hosting.categories.show');

Route::get('/shopping-cart', CartController::class)->name('cart.index');
Route::get('/domains', [SearchDomainController::class, 'index'])->name('domains');
Route::post('/domains/search', [SearchDomainController::class, 'search'])->name('domains.search'); // redirects to GET /domains?domain=...

Route::get('/dashboard', DashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');

Route::group(['middleware' => ['auth', 'verified'], 'prefix' => 'admin', 'as' => 'admin.'], function (): void {
    Route::resource('contacts', ContactController::class);
    Route::resource('tlds', TldController::class)->except(['show']);
    Route::resource('tld-pricings', TldPricingController::class)->except(['show']);

    // Custom domain registration with hosting subscription (must be before resource route)
    Route::get('domains/custom-register', [DomainController::class, 'createCustom'])->name('domains.custom-register');
    Route::post('domains/custom-register', [DomainController::class, 'storeCustom'])->name('domains.custom-register.store');

    // Custom domain registration edit (must be before resource route)
    Route::get('domains/{domain:uuid}/edit-registration', [DomainController::class, 'editCustom'])->name('domains.edit-registration');
    Route::put('domains/{domain:uuid}/edit-registration', [DomainController::class, 'updateCustom'])->name('domains.update-registration');

    Route::resource('domains', DomainController::class)->except(['show', 'edit']);

    Route::get('domains/{domain:uuid}/info', [DomainOperationsController::class, 'domainInfo'])->name('domains.info');
    Route::post('domains/{domain:uuid}/refresh-info', [DomainController::class, 'refreshInfo'])->name('domains.refresh-info');
    Route::post('/domains/{domain:uuid}/fetch-contacts', [DomainOperationsController::class, 'getContacts'])->name('domains.fetch-contacts');
    Route::get('domains/{domain:uuid}/contacts/{type}/edit', [DomainController::class, 'editContact'])->name('domains.contacts.edit');
    Route::put('domains/{domain:uuid}/contacts', [DomainController::class, 'updateContacts'])->name('domains.contacts.update');
    Route::match(['post', 'put'], 'domains/{domain:uuid}/lock', [DomainController::class, 'toggleLock'])->name('domains.lock');
    Route::get('domains/{domain:uuid}/transfer', [DomainController::class, 'showTransferForm'])->name('domains.transfer');
    Route::post('domains/{domain:uuid}/transfer', [DomainController::class, 'transferDomain'])->name('domains.transfer.store');
    Route::get('domains/{domain:uuid}/ownership', [DomainController::class, 'ownerShipForm'])->name('domains.assign');
    Route::post('domains/{domain:uuid}/ownership', [DomainController::class, 'assignOwner'])->name('domains.assign.store');
    Route::post('domains/{domain:uuid}/reactivate', [DomainController::class, 'reactivate'])->name('domains.reactivate');
    Route::get('domains/{domain:uuid}/nameservers', [DomainController::class, 'edit'])->name('domains.edit');
    Route::put('domains/{domain:uuid}/nameservers', [DomainController::class, 'updateNameservers'])->name('domains.nameservers.update');

    Route::resource('hosting-categories', HostingCategoryController::class)->except(['show']);
    Route::resource('hosting-plans', HostingPlanController::class)->except(['show']);
    Route::resource('hosting-plan-prices', HostingPlanPriceController::class)->except(['show']);
    Route::resource('hosting-promotions', HostingPromotionController::class)->except(['show']);
    Route::resource('hosting-plan-features', HostingPlanFeatureController::class)->except(['show']);
    Route::resource('feature-categories', FeatureCategoryController::class)->except(['show']);
    Route::resource('hosting-features', HostingFeatureController::class)->except(['show']);
    Route::resource('permissions', PermissionsController::class);
    Route::resource('roles', RolesController::class);
    Route::resource('users', UsersController::class);
    Route::resource('prices', TldController::class)->except(['show']);
    Route::resource('currencies', CurrencyController::class);
    Route::resource('settings', SettingController::class);
    Route::get('subscriptions', [SubscriptionController::class, 'index'])->name('subscriptions.index');
    Route::get('subscriptions/create', [SubscriptionController::class, 'create'])->name('subscriptions.create');
    Route::post('subscriptions', [SubscriptionController::class, 'store'])->name('subscriptions.store');
    Route::get('subscriptions/{subscription}', [SubscriptionController::class, 'show'])->name('subscriptions.show');
    Route::get('subscriptions/{subscription}/edit', [SubscriptionController::class, 'edit'])->name('subscriptions.edit');
    Route::put('subscriptions/{subscription}', [SubscriptionController::class, 'update'])->name('subscriptions.update');
    Route::post('subscriptions/{subscription}/renew', [SubscriptionController::class, 'renewNow'])->name('subscriptions.renew-now');

    // Failed domain registration routes
    Route::get('failed-registrations', [FailedDomainRegistrationController::class, 'index'])->name('failed-registrations.index');
    Route::get('failed-registrations/manual-register', [FailedDomainRegistrationController::class, 'manualRegisterForm'])->name('failed-registrations.manual-register');
    Route::post('failed-registrations/manual-register', [FailedDomainRegistrationController::class, 'manualRegisterStore'])->name('failed-registrations.manual-register.store');
    Route::get('failed-registrations/{failedDomainRegistration}', [FailedDomainRegistrationController::class, 'show'])->name('failed-registrations.show');
    Route::post('failed-registrations/{failedDomainRegistration}/retry', [FailedDomainRegistrationController::class, 'retry'])->name('failed-registrations.retry');

    // Notification routes
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/{id}/mark-read', [NotificationController::class, 'markAsRead'])->name('notifications.mark-read');
    Route::post('notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
    Route::delete('notifications/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
    Route::delete('notifications', [NotificationController::class, 'destroyAll'])->name('notifications.destroy-all');

    Route::get('audit-logs', AuditLogController::class)->name('audit-logs.index');
    Route::get('domain-price-history', [DomainPriceHistoryController::class, 'index'])->name('domain-price-history.index');
    Route::get('hosting-plan-price-history', [HostingPlanPriceHistoryController::class, 'index'])->name('hosting-plan-price-history.index');
    Route::get('products/domains', [ProductController::class, 'domains'])->name('products.domains');
    Route::get('products/hosting', [ProductController::class, 'hosting'])->name('products.hosting');
    Route::get('products/subscriptions/{subscription}', [ProductController::class, 'showSubscription'])->name('products.subscription.show');
    Route::post('products/subscriptions/{subscription}/renew', [ProductController::class, 'addSubscriptionRenewalToCart'])->name('products.subscription.renew');

    // VPS management routes
    Route::get('vps', [VpsController::class, 'index'])->name('vps.index');
    Route::get('vps/assign', [VpsController::class, 'assign'])->name('vps.assign');
    Route::post('vps/assign', [VpsController::class, 'storeAssignment'])->name('vps.assign.store');
    Route::get('vps/{subscription}', [VpsController::class, 'show'])->name('vps.show');
    Route::post('vps/{subscription}/restart', [VpsController::class, 'restart'])->name('vps.restart');
    Route::post('vps/{subscription}/shutdown', [VpsController::class, 'shutdown'])->name('vps.shutdown');
    Route::post('vps/{subscription}/reinstall', [VpsController::class, 'reinstall'])->name('vps.reinstall');
    Route::post('vps/{subscription}/rescue', [VpsController::class, 'rescue'])->name('vps.rescue');
    Route::post('vps/{subscription}/reset-credentials', [VpsController::class, 'resetCredentials'])->name('vps.reset-credentials');
    Route::post('vps/{subscription}/display-name', [VpsController::class, 'changeDisplayName'])->name('vps.display-name');
    Route::post('vps/{subscription}/snapshots', [VpsController::class, 'createSnapshot'])->name('vps.snapshots.store');
    Route::delete('vps/{subscription}/snapshots/{snapshotId}', [VpsController::class, 'deleteSnapshot'])->name('vps.snapshots.destroy');
    Route::post('vps/{subscription}/snapshots/{snapshotId}/restore', [VpsController::class, 'restoreSnapshot'])->name('vps.snapshots.restore');
    Route::post('vps/{subscription}/upgrade', [VpsController::class, 'upgrade'])->name('vps.upgrade');
    Route::post('vps/{subscription}/order-license', [VpsController::class, 'orderLicense'])->name('vps.order-license');
    Route::post('vps/{subscription}/extend-storage', [VpsController::class, 'extendStorage'])->name('vps.extend-storage');
    Route::post('vps/{subscription}/move-region', [VpsController::class, 'moveRegion'])->name('vps.move-region');
    Route::post('vps/{subscription}/cancel', [VpsController::class, 'cancel'])->name('vps.cancel');

});

Route::middleware(['auth', 'verified'])->group(function (): void {

    // User VPS routes
    Route::get('/vps', [UserVpsController::class, 'index'])->name('user.vps.index');
    Route::get('/vps/{subscription}', [UserVpsController::class, 'show'])->name('user.vps.show');
    Route::post('/vps/{subscription}/restart', [UserVpsController::class, 'restart'])->name('user.vps.restart');
    Route::post('/vps/{subscription}/shutdown', [UserVpsController::class, 'shutdown'])->name('user.vps.shutdown');
    Route::post('/vps/{subscription}/rescue', [UserVpsController::class, 'rescue'])->name('user.vps.rescue');
    Route::post('/vps/{subscription}/reset-credentials', [UserVpsController::class, 'resetCredentials'])->name('user.vps.reset-credentials');
    Route::post('/vps/{subscription}/display-name', [UserVpsController::class, 'changeDisplayName'])->name('user.vps.display-name');
    Route::post('/vps/{subscription}/snapshots', [UserVpsController::class, 'createSnapshot'])->name('user.vps.snapshots.store');
    Route::delete('/vps/{subscription}/snapshots/{snapshotId}', [UserVpsController::class, 'deleteSnapshot'])->name('user.vps.snapshots.destroy');
    Route::post('/vps/{subscription}/snapshots/{snapshotId}/restore', [UserVpsController::class, 'restoreSnapshot'])->name('user.vps.snapshots.restore');
    Route::post('/vps/{subscription}/reinstall', [UserVpsController::class, 'reinstall'])->name('user.vps.reinstall');
    Route::post('/vps/{subscription}/upgrade', [UserVpsController::class, 'upgrade'])->name('user.vps.upgrade');

    Route::get('shopping-cart/checkout', [SmartCheckoutController::class, 'index'])->name('checkout.index');

    Route::get('/cart/checkout/payment/', [RenewalCheckoutController::class, 'index'])->name('checkout.renewal');
    Route::get('/checkout/success/{order}', [RenewalCheckoutController::class, 'success'])->name('checkout.success');
    Route::get('/checkout/cancel', [RenewalCheckoutController::class, 'cancel'])->name('checkout.cancel');
    Route::get('/checkout/stripe/redirect/{order}', [RenewalCheckoutController::class, 'stripeRedirect'])->name('checkout.stripe.redirect');
    Route::get('/checkout/stripe/success/{order}', [RenewalCheckoutController::class, 'stripeSuccess'])->name('checkout.stripe.success');
    Route::get('/checkout/stripe/cancel/{order}', [RenewalCheckoutController::class, 'stripeCancel'])->name('checkout.stripe.cancel');
    Route::get('/domains/register', [RegisterDomainController::class, 'index'])->name('domains.register');
    Route::post('/domains/register', [RegisterDomainController::class, 'register'])->name('domains.register.store');

    // Domain renewal routes
    Route::get('/domains/{domain}/renew', [DomainRenewalController::class, 'show'])->name('domains.renew.show');
    Route::post('/domains/{domain}/renew', [DomainRenewalController::class, 'addToCart'])->name('domains.renew.add-to-cart');

    // Subscription renewal routes
    Route::get('/subscriptions/{subscription}/renew', [SubscriptionRenewalController::class, 'show'])->name('subscriptions.renew.show');
    Route::post('/subscriptions/{subscription}/renew', [SubscriptionRenewalController::class, 'addToCart'])->name('subscriptions.renew.add-to-cart');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/address', [ProfileController::class, 'updateAddress'])->name('address.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Billing
    Route::get('/billing', [BillingController::class, 'index'])->name('billing.index');
    Route::get('/billing/{order:order_number}', [BillingController::class, 'show'])->name('billing.show');
    Route::get('/billing/{order:order_number}/invoice', [BillingController::class, 'invoice'])->name('billing.invoice');
    Route::get('/billing/{order:order_number}/invoice/download', [BillingController::class, 'downloadInvoice'])->name('billing.invoice.download');
    Route::get('/billing/{order:order_number}/invoice/view-pdf', [BillingController::class, 'viewInvoicePdf'])->name('billing.invoice.view-pdf');

    Route::get('/contacts/{id}/details', [App\Http\Controllers\Api\ContactController::class, 'details'])->name('contacts.details');
    Route::get('/api/contacts/{id}', [App\Http\Controllers\Api\ContactController::class, 'details'])->name('api.contacts.details');

    // Payment routes
    Route::post('/payment/stripe', [PaymentController::class, 'stripeCheckout'])->name('payment.stripe');
    Route::get('/payment/success/{order}', [PaymentController::class, 'success'])->name('payment.success');
    Route::get('/payment/cancel/{order}', [PaymentController::class, 'handlePaymentCancel'])->name('payment.cancel');
    Route::get('/payment/failed/{order}', [PaymentController::class, 'showPaymentFailed'])->name('payment.failed');

    // KPay Payment Routes
    Route::get('/payment/kpay', [KpayPaymentController::class, 'show'])->name('payment.kpay.show');
    Route::post('/payment/kpay', [KpayPaymentController::class, 'process'])->name('payment.kpay');
    Route::get('/payment/kpay/success/{order}', [KpayPaymentController::class, 'success'])->name('payment.kpay.success');
    Route::get('/payment/kpay/cancel/{order}', [KpayPaymentController::class, 'cancel'])->name('payment.kpay.cancel');
    Route::get('/payment/kpay/status/{payment}', [KpayPaymentController::class, 'status'])->name('payment.kpay.status');
});

Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook'])->name('stripe.webhook');

Route::post('/payment/kpay/webhook', [KPayWebhookController::class, 'handlePostback'])->name('payment.kpay.webhook');

require __DIR__.'/auth.php';
