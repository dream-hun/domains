<?php

declare(strict_types=1);

use App\Http\Middleware\SetCurrency;
use App\Models\Address;
use App\Models\Currency;
use App\Models\User;
use App\Services\GeolocationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

beforeEach(function (): void {
    Session::flush();
});

function createMiddleware(): SetCurrency
{
    return new SetCurrency(resolve(GeolocationService::class));
}

function createRequestWithUser(?User $user = null): Request
{
    $request = Request::create('/');

    if ($user instanceof User) {
        $request->setUserResolver(fn (): User => $user);
    }

    return $request;
}

it('sets currency from query parameter when provided', function (): void {
    Currency::factory()->create(['code' => 'EUR', 'is_active' => true]);

    $request = Request::create('/', 'GET', ['currency' => 'EUR']);
    createMiddleware()->handle($request, fn ($req) => response()->make('OK'));

    expect(session('selected_currency'))->toBe('EUR');
});

it('uses authenticated user preferred currency from address when no session currency exists', function (): void {
    $user = User::factory()->create();
    Address::factory()->create([
        'user_id' => $user->id,
        'preferred_currency' => 'RWF',
    ]);

    createMiddleware()->handle(
        createRequestWithUser($user),
        fn ($req) => response()->make('OK')
    );

    expect(session('selected_currency'))->toBe('RWF');
});

it('uses geolocation when authenticated user has no address', function (): void {
    config(['app.local_default_country' => 'RW']);

    $user = User::factory()->create();

    createMiddleware()->handle(
        createRequestWithUser($user),
        fn ($req) => response()->make('OK')
    );

    expect(session('selected_currency'))->toBe('RWF');
});

it('uses geolocation when authenticated user address has no preferred currency', function (): void {
    config(['app.local_default_country' => 'US']);

    $user = User::factory()->create();
    Address::factory()->create([
        'user_id' => $user->id,
        'preferred_currency' => null,
    ]);

    createMiddleware()->handle(
        createRequestWithUser($user),
        fn ($req) => response()->make('OK')
    );

    expect(session('selected_currency'))->toBe('USD');
});

it('uses geolocation when authenticated user preferred currency is not active', function (): void {
    config(['app.local_default_country' => 'US']);

    Currency::factory()->create(['code' => 'INACTIVE', 'is_active' => false]);

    $user = User::factory()->create();
    Address::factory()->create([
        'user_id' => $user->id,
        'preferred_currency' => 'INACTIVE',
    ]);

    createMiddleware()->handle(
        createRequestWithUser($user),
        fn ($req) => response()->make('OK')
    );

    expect(session('selected_currency'))->toBe('USD');
});

it('uses geolocation for unauthenticated users', function (): void {
    config(['app.local_default_country' => 'US']);

    createMiddleware()->handle(
        createRequestWithUser(),
        fn ($req) => response()->make('OK')
    );

    expect(session('selected_currency'))->toBe('USD');
});

it('prioritizes query parameter over user preferred currency', function (): void {
    Currency::factory()->create(['code' => 'EUR', 'is_active' => true]);

    $user = User::factory()->create();
    Address::factory()->create([
        'user_id' => $user->id,
        'preferred_currency' => 'RWF',
    ]);

    $request = Request::create('/', 'GET', ['currency' => 'EUR']);
    $request->setUserResolver(fn () => $user);

    createMiddleware()->handle($request, fn ($req) => response()->make('OK'));

    expect(session('selected_currency'))->toBe('EUR');
});

it('prioritizes user preferred currency over session currency', function (): void {
    config(['app.local_default_country' => 'US']);

    $user = User::factory()->create();
    Address::factory()->create([
        'user_id' => $user->id,
        'preferred_currency' => 'RWF',
    ]);

    Session::put('selected_currency', 'USD');

    createMiddleware()->handle(
        createRequestWithUser($user),
        fn ($req) => response()->make('OK')
    );

    expect(session('selected_currency'))->toBe('RWF');
});

it('prioritizes user preferred currency over geolocation', function (): void {
    $user = User::factory()->create();
    Address::factory()->create([
        'user_id' => $user->id,
        'preferred_currency' => 'EUR',
    ]);

    Currency::factory()->create(['code' => 'EUR', 'is_active' => true]);

    createMiddleware()->handle(
        createRequestWithUser($user),
        fn ($req) => response()->make('OK')
    );

    expect(session('selected_currency'))->toBe('EUR');
});

it('keeps session currency when it matches geolocation', function (): void {
    config(['app.local_default_country' => 'RW']);

    Session::put('selected_currency', 'RWF');

    createMiddleware()->handle(
        createRequestWithUser(),
        fn ($req) => response()->make('OK')
    );

    expect(session('selected_currency'))->toBe('RWF');
});

it('preserves valid session currency even when geolocation differs for authenticated user', function (): void {
    config(['app.local_default_country' => 'RW']);

    $user = User::factory()->create();

    Session::put('selected_currency', 'USD');

    createMiddleware()->handle(
        createRequestWithUser($user),
        fn ($req) => response()->make('OK')
    );

    expect(session('selected_currency'))->toBe('USD');
});

it('preserves valid non-geolocation session currency for unauthenticated users', function (): void {
    config(['app.local_default_country' => 'RW']);

    Session::put('selected_currency', 'USD');

    createMiddleware()->handle(
        createRequestWithUser(),
        fn ($req) => response()->make('OK')
    );

    expect(session('selected_currency'))->toBe('USD');
});
