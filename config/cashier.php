<?php

declare(strict_types=1);

use App\Models\User;

return [
    /*
    |--------------------------------------------------------------------------
    | Stripe Keys
    |--------------------------------------------------------------------------
    |
    | The Stripe publishable and secret key give you access to Stripe's
    | API. The "publishable" key is typically used when interacting with
    | Stripe.js while the "secret" key accesses private API endpoints.
    |
    */

    'key' => env('STRIPE_KEY'),

    'secret' => env('STRIPE_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Cashier Path
    |--------------------------------------------------------------------------
    |
    | This is the base URI path that will be used when generating URLs to the
    | Cashier payment pages. This path will be concatenated with the base
    | URL of your application when generating URLs for payment pages.
    |
    */

    'path' => env('CASHIER_PATH', 'stripe'),

    /*
    |--------------------------------------------------------------------------
    | Cashier Webhook
    |--------------------------------------------------------------------------
    |
    | This is the base URI that will be used when generating URLs for webhooks.
    | This path will be concatenated with the base URL of your application
    | when generating URLs for webhooks.
    |
    */

    'webhook' => [
        'secret' => env('STRIPE_WEBHOOK_SECRET'),
        'tolerance' => env('STRIPE_WEBHOOK_TOLERANCE', 300),
        'events' => [
            'customer.subscription.created',
            'customer.subscription.updated',
            'customer.subscription.deleted',
            'customer.updated',
            'customer.deleted',
            'payment_method.automatically_updated',
            'invoice.payment_action_required',
            'invoice.payment_succeeded',
            'checkout.session.completed',
            'payment_intent.succeeded',
            'payment_intent.payment_failed',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cashier Model
    |--------------------------------------------------------------------------
    |
    | This is the model in your application that includes the Billable trait
    | provided by Cashier. It will serve as the primary model you use while
    | interacting with Cashier related methods, subscriptions, and so on.
    |
    */

    'model' => env('CASHIER_MODEL', User::class),

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    |
    | This is the default currency that will be used when generating charges
    | from your application. Of course, you are welcome to use any of the
    | various world currencies that are currently supported via Stripe.
    |
    */

    'currency' => env('CASHIER_CURRENCY', 'usd'),

    /*
    |--------------------------------------------------------------------------
    | Currency Locale
    |--------------------------------------------------------------------------
    |
    | This is the default locale in which your money values are formatted in
    | for display. To utilize other locales besides the default en locale
    | verify you have the "intl" PHP extension installed on the system.
    |
    */

    'currency_locale' => env('CASHIER_CURRENCY_LOCALE', 'en'),

    /*
    |--------------------------------------------------------------------------
    | Payment Notification
    |--------------------------------------------------------------------------
    |
    | This is the notification class that will be used when sending payment
    | notifications. You can change this to any class that extends the
    | default notification class.
    |
    */

    'payment_notification' => null,

    /*
    |--------------------------------------------------------------------------
    | Invoice Renderer
    |--------------------------------------------------------------------------
    |
    | This is the class responsible for rendering PDF invoices. You may
    | customize this class to render invoices according to your needs.
    |
    */

    'invoices' => [
        'renderer' => 'Laravel\\Cashier\\Invoices\\DompdfInvoiceRenderer',
        'options' => [
            'paper' => 'letter',
            'remote_enabled' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logger
    |--------------------------------------------------------------------------
    |
    | This is the logger that will be used to log Cashier related events.
    | You can change this to any class that implements the Psr\Log\LoggerInterface
    | interface.
    |
    */

    'logger' => null,
];
