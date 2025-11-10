<?php

declare(strict_types=1);

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
    expect(Cart::getContent()->first()->name)->toBe($this->domain->name);
    expect(Cart::getContent()->first()->quantity)->toBe(2);
});

it('validates renewal years', function (): void {
    $response = actingAs($this->user)
        ->post(route('domains.renew.add-to-cart', $this->domain), [
            'years' => 15, // Invalid: max is 10
        ]);

    $response->assertSessionHasErrors('years');
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
