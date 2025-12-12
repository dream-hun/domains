<?php

declare(strict_types=1);

use App\Enums\DomainType;
use App\Models\Domain;
use App\Models\DomainPrice;
use App\Models\Role;
use App\Models\User;
use Darryldecode\Cart\Facades\CartFacade as Cart;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function (): void {
    Role::query()->firstOrCreate(
        ['id' => 1],
        ['title' => 'Admin']
    );

    Role::query()->firstOrCreate(
        ['id' => 2],
        ['title' => 'Customer']
    );

    $this->user = User::factory()->create();

    // Create a domain price
    $this->domainPrice = DomainPrice::factory()->create([
        'tld' => '.com',
        'renewal_price' => 1500, // $15.00
    ]);

    // Create a domain owned by the user
    $this->domain = Domain::factory()->create([
        'name' => 'example.com',
        'owner_id' => $this->user->id,
        'expires_at' => now()->addMonths(2),
    ]);

    Cart::clear();
});

it('shows the domain renewal page to the owner', function (): void {
    $response = actingAs($this->user)
        ->get(route('domains.renew.show', $this->domain));

    $response->assertStatus(200);
    $response->assertSee($this->domain->name);
    $response->assertSee('Renewal Period');
});

it('prevents non-owners from accessing renewal page', function (): void {
    $otherUser = User::factory()->create();

    $response = actingAs($otherUser)
        ->get(route('domains.renew.show', $this->domain));

    $response->assertStatus(403);
});

it('allows admin to access any domain renewal page', function (): void {
    $admin = User::factory()->create();
    $admin->roles()->attach(1);

    $response = actingAs($admin)
        ->get(route('domains.renew.show', $this->domain));

    $response->assertOk();
});

it('adds domain to cart for renewal', function (): void {
    $response = actingAs($this->user)
        ->post(route('domains.renew.add-to-cart', $this->domain), [
            'years' => 2,
        ]);

    $response->assertRedirect(route('checkout.index'));
    $response->assertSessionHas('success');

    expect(Cart::getContent())->not->toBeEmpty();
    expect(Cart::getContent()->first()->name)->toBe($this->domain->name.' (Renewal)');
    expect(Cart::getContent()->first()->quantity)->toBe(2);
});

it('validates renewal years', function (): void {
    $response = actingAs($this->user)
        ->post(route('domains.renew.add-to-cart', $this->domain), [
            'years' => 15, // Invalid: max is 10
        ]);

    $response->assertSessionHasErrors('years');
});

it('requires renewal totals to meet stripe minimum', function (): void {
    $lowPrice = DomainPrice::factory()->create([
        'tld' => '.rw',
        'renewal_price' => 10, // $0.10
        'register_price' => 10,
        'transfer_price' => 10,
        'type' => DomainType::Local,
    ]);

    $domain = Domain::factory()
        ->for($this->user, 'owner')
        ->for($lowPrice, 'domainPrice')
        ->create([
            'name' => 'cheap.rw',
            'expires_at' => now()->addMonth(),
        ]);

    $response = actingAs($this->user)
        ->post(route('domains.renew.add-to-cart', $domain), [
            'years' => 1,
        ]);

    $response->assertSessionHas('error');

    expect(Cart::getContent())->toBeEmpty();
});

it('adds local renewals that meet stripe minimum in RWF', function (): void {
    Cart::clear();

    $rwPrice = DomainPrice::factory()->create([
        'tld' => '.rw',
        'type' => DomainType::Local,
        'renewal_price' => 16000,
        'register_price' => 16000,
        'transfer_price' => 16000,
    ]);

    $rwDomain = Domain::factory()
        ->for($this->user, 'owner')
        ->for($rwPrice, 'domainPrice')
        ->create([
            'name' => 'valid.rw',
            'expires_at' => now()->addMonth(),
        ]);

    $response = actingAs($this->user)
        ->post(route('domains.renew.add-to-cart', $rwDomain), [
            'years' => 1,
        ]);

    $response->assertRedirect(route('checkout.index'));
    $response->assertSessionHas('success');

    $cartItem = Cart::getContent()->first();

    expect($cartItem)->not->toBeNull()
        ->and($cartItem->price)->toBe(16000.0)
        ->and($cartItem->quantity)->toBe(1)
        ->and($cartItem->attributes->currency)->toBe('RWF')
        ->and($cartItem->attributes->get('total_price'))->toBe(16000.0);
});

it('requires authentication for renewal', function (): void {
    $response = get(route('domains.renew.show', $this->domain));

    $response->assertRedirect(route('login'));
});

it('shows checkout page with cart items', function (): void {
    // Add item to cart
    Cart::add([
        'id' => $this->domain->id,
        'name' => $this->domain->name,
        'price' => 15.00,
        'quantity' => 1,
        'attributes' => [
            'type' => 'renewal',
            'years' => 1,
            'currency' => 'USD',
        ],
    ]);

    $response = actingAs($this->user)
        ->get(route('checkout.index'));

    $response->assertStatus(200);
    $response->assertSee($this->domain->name);
    $response->assertSee('Checkout');
});

it('redirects to dashboard if cart is empty', function (): void {
    Cart::clear();

    $response = actingAs($this->user)
        ->get(route('checkout.index'));

    $response->assertRedirect(route('dashboard'));
    $response->assertSessionHas('error');
});

it('allows multiple items in cart including renewals', function (): void {
    // Add a domain to cart first
    Cart::add([
        'id' => 'example2.com',
        'name' => 'example2.com',
        'price' => 10.00,
        'quantity' => 1,
        'attributes' => [
            'type' => 'domain',
            'domain_name' => 'example2.com',
            'currency' => 'USD',
        ],
    ]);

    // Add renewal for another domain
    $response = actingAs($this->user)
        ->post(route('domains.renew.add-to-cart', $this->domain), [
            'years' => 1,
        ]);

    $response->assertRedirect(route('checkout.index'));
    $response->assertSessionHas('success');

    // Verify both items are in cart
    $cartItems = Cart::getContent();
    expect($cartItems)->toHaveCount(2);
    expect($cartItems->pluck('name')->toArray())->toContain('example2.com', 'example.com (Renewal)');
});

it('prevents adding duplicate renewals to cart', function (): void {
    // Add renewal first time
    $response1 = actingAs($this->user)
        ->post(route('domains.renew.add-to-cart', $this->domain), [
            'years' => 1,
        ]);

    $response1->assertRedirect(route('checkout.index'));

    // Try to add same renewal again
    $response2 = actingAs($this->user)
        ->post(route('domains.renew.add-to-cart', $this->domain), [
            'years' => 1,
        ]);

    $response2->assertSessionHas('error');

    expect(Cart::getContent())->toHaveCount(1);
});
