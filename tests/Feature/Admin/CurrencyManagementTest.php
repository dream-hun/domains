<?php

declare(strict_types=1);

use App\Models\Currency;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\CurrencyService;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    // Create both necessary roles (user model automatically attaches role id 2)
    Role::query()->firstOrCreate(['id' => 1, 'title' => 'Admin']);
    Role::query()->firstOrCreate(['id' => 2, 'title' => 'User']);

    // Create admin user
    $this->admin = User::factory()->create();

    // Attach admin role and permissions
    $adminRole = Role::query()->find(1);
    $permissionIds = [
        Permission::query()->firstOrCreate(['id' => 48, 'title' => 'currency_access'])->id,
        Permission::query()->firstOrCreate(['id' => 49, 'title' => 'currency_create'])->id,
        Permission::query()->firstOrCreate(['id' => 50, 'title' => 'currency_edit'])->id,
        Permission::query()->firstOrCreate(['id' => 51, 'title' => 'currency_delete'])->id,
        Permission::query()->firstOrCreate(['id' => 52, 'title' => 'currency_update_rates'])->id,
    ];
    $adminRole->permissions()->sync($permissionIds);
    $this->admin->roles()->sync([1]); // Detach default role 2, attach admin role 1
});

it('displays currencies on index page', function (): void {
    Currency::factory()->create(['code' => 'USD', 'is_base' => true]);
    Currency::factory()->create(['code' => 'EUR', 'is_base' => false]);

    $response = $this->actingAs($this->admin)->get('/admin/currencies');

    $response->assertStatus(200);
    $response->assertSee('USD');
    $response->assertSee('EUR');
});

it('allows admin to create a new currency', function (): void {
    $this->actingAs($this->admin)->post('/admin/currencies', [
        'code' => 'GBP',
        'name' => 'British Pound',
        'symbol' => '£',
        'exchange_rate' => '1.25',
        'is_active' => '1',
        'is_base' => '0',
    ])->assertRedirect('/admin/currencies');

    $this->assertDatabaseHas('currencies', [
        'code' => 'GBP',
        'name' => 'British Pound',
        'symbol' => '£',
    ]);
});

it('validates required fields when creating currency', function (): void {
    $response = $this->actingAs($this->admin)->post('/admin/currencies', []);

    $response->assertSessionHasErrors(['code', 'name', 'symbol', 'exchange_rate']);
});

it('validates currency code is exactly 3 uppercase letters', function (): void {
    $response = $this->actingAs($this->admin)->post('/admin/currencies', [
        'code' => 'abcd',
        'name' => 'Test',
        'symbol' => '$',
        'exchange_rate' => '1.0',
    ]);

    $response->assertSessionHasErrors('code');
});

it('allows admin to update a currency', function (): void {
    $currency = Currency::factory()->create(['code' => 'USD', 'name' => 'US Dollar']);

    $this->actingAs($this->admin)->put('/admin/currencies/'.$currency->id, [
        'name' => 'United States Dollar',
        'symbol' => '$',
        'exchange_rate' => '1.0',
    ])->assertRedirect('/admin/currencies');

    $this->assertDatabaseHas('currencies', [
        'id' => $currency->id,
        'name' => 'United States Dollar',
    ]);
});

it('allows toggling active status of a currency', function (): void {
    $currency = Currency::factory()->create(['is_active' => true]);

    $this->actingAs($this->admin)->put('/admin/currencies/'.$currency->id, [
        'name' => $currency->name,
        'symbol' => $currency->symbol,
        'exchange_rate' => $currency->exchange_rate,
        'is_active' => '0',
    ]);

    expect($currency->fresh()->is_active)->toBeFalse();
});

it('prevents deletion of base currency', function (): void {
    $currency = Currency::factory()->create(['code' => 'USD', 'is_base' => true]);

    $response = $this->actingAs($this->admin)->delete('/admin/currencies/'.$currency->id);

    $response->assertSessionHas('error');
    $this->assertDatabaseHas('currencies', ['id' => $currency->id]);
});

it('allows deletion of non-base currency', function (): void {
    $currency = Currency::factory()->create(['code' => 'EUR', 'is_base' => false]);

    $this->actingAs($this->admin)->delete('/admin/currencies/'.$currency->id)
        ->assertRedirect('/admin/currencies');

    $this->assertDatabaseMissing('currencies', ['id' => $currency->id]);
});

it('ensures only one base currency exists', function (): void {
    Currency::factory()->create(['code' => 'USD', 'is_base' => true]);
    $eur = Currency::factory()->create(['code' => 'EUR', 'is_base' => false]);

    $this->actingAs($this->admin)->put('/admin/currencies/'.$eur->id, [
        'name' => $eur->name,
        'symbol' => $eur->symbol,
        'exchange_rate' => $eur->exchange_rate,
        'is_base' => '1',
    ]);

    expect(Currency::query()->where('is_base', true)->count())->toBe(1);
    expect($eur->fresh()->is_base)->toBeTrue();
});

it('requires authentication to access currencies', function (): void {
    $this->get('/admin/currencies')->assertRedirect('/login');
});

it('requires permission to access currencies', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)->get('/admin/currencies')->assertForbidden();
});

it('clears all user carts when exchange rates are updated', function (): void {
    // Create a user session with cart data
    $sessionId = 'test-session-123';
    $cartData = ['cart' => ['items' => [['id' => 'test-domain', 'price' => 10]]]];

    DB::table('sessions')->insert([
        'id' => $sessionId,
        'user_id' => null,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'test',
        'payload' => base64_encode(serialize($cartData)),
        'last_activity' => now()->timestamp,
    ]);

    // Verify cart exists
    $session = DB::table('sessions')->where('id', $sessionId)->first();
    expect(unserialize(base64_decode((string) $session->payload, true)))->toHaveKey('cart');

    // Update exchange rates
    $currencyService = app(CurrencyService::class);
    $currencyService->clearAllCarts();

    // Verify cart is cleared
    $session = DB::table('sessions')->where('id', $sessionId)->first();
    $payload = unserialize(base64_decode((string) $session->payload, true));
    expect($payload)->not->toHaveKey('cart');
});

it('prevents non-admins from accessing update rates', function (): void {
    $user = User::factory()->create();

    // Don't mock the client, just let it fail gracefully at the permission check
    // The API key issue shouldn't matter since we're testing permission enforcement
    $this->actingAs($user)->post('/admin/currencies/update-rates')->assertForbidden();
});
