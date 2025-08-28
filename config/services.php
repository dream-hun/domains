<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
   |--------------------------------------------------------------------------
   | EPP Credentials
   |--------------------------------------------------------------------------
   */

    'epp' => [
        'host' => env('EPP_HOST'),
        'username' => env('EPP_USERNAME'),
        'password' => env('EPP_PASSWORD'),
        'port' => env('EPP_PORT'),
        'ssl' => env('EPP_SSL'),
        'certificate' => storage_path('app/public/certificate.pem'),
    ],
    /*
    |--------------------------------------------------------------------------
    | Namecheap API
    |--------------------------------------------------------------------------
    */

    'namecheap' => [
        'apiUser' => env('NAMECHEAP_USERNAME', ''),
        'apiKey' => env('NAMECHEAP_API_KEY', ''),
        'username' => env('NAMECHEAP_USERNAME', ''),
        'client' => env('NAMECHEAP_CLIENT_IP', ''),
        'apiBaseUrl' => env('NAMECHEAP_API_URL', ''),
        'sandbox' => env('NAMECHEAP_SANDBOX', false),
    ],

    /**
     * Recaptcha configurations of environment we are working on
     */
    'recaptcha' => [
        'site_key' => env('RECAPTCHA_SITE_KEY'),
        'secret_key' => env('RECAPTCHA_SECRET_KEY'),
        'min_score' => env('RECAPTCHA_MIN_SCORE', .5),
    ],

];
