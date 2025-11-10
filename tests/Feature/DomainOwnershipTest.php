<?php

declare(strict_types=1);

use App\Actions\RegisterDomainAction;
use App\Models\Contact;
use App\Models\Country;
use App\Models\Domain;
use App\Models\DomainPrice;
use App\Models\User;
use App\Services\Domain\DomainRegistrationServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('domain registration sets owner_id correctly with authenticated user', function (): void {
    // Create test user and authenticate
    $user = User::factory()->create();
    $this->actingAs($user);

    // Create test country
    Country::factory()->create(['iso_code' => 'US']);

    // Create test contact
    $contact = Contact::factory()->create([
        'user_id' => $user->id,
        'country_code' => 'US',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@example.com',
        'phone' => '+1.1234567890',
        'address_one' => '123 Main St',
        'city' => 'Anytown',
        'state_province' => 'CA',
        'postal_code' => '12345',
    ]);

    // Create domain price
    DomainPrice::factory()->create([
        'tld' => '.com',
        'register_price' => 1299,
    ]);

    // Mock the Namecheap domain service
    $mockService = $this->mock(DomainRegistrationServiceInterface::class);
    $mockService->shouldReceive('registerDomain')
        ->once()
        ->andReturn([
            'success' => true,
            'domain' => 'example.com',
            'message' => 'Domain registered successfully',
        ]);
    $mockService->shouldReceive('updateNameservers')
        ->once()
        ->andReturn(['success' => true]);

    app()->instance('namecheap_domain_service', $mockService);

    $action = app(RegisterDomainAction::class);

    // Register domain without explicitly passing userId (should use authenticated user)
    $result = $action->handle(
        'example.com',
        [
            'registrant' => $contact->id,
            'admin' => $contact->id,
            'technical' => $contact->id,
            'billing' => $contact->id,
        ],
        1,
        ['ns1.example.com', 'ns2.example.com']
    );

    expect($result['success'])->toBeTrue();
    expect($result['domain_id'])->not->toBeNull();

    // Verify domain was created with correct owner_id
    $domain = Domain::query()->find($result['domain_id']);
    expect($domain)->not->toBeNull();
    expect($domain->owner_id)->toBe($user->id);
    expect($domain->owner->id)->toBe($user->id);
});

test('domain registration sets owner_id correctly with explicit user id', function (): void {
    // Create test user (not authenticated)
    $user = User::factory()->create();

    // Create test country
    Country::factory()->create(['iso_code' => 'US']);

    // Create test contact
    $contact = Contact::factory()->create([
        'user_id' => $user->id,
        'country_code' => 'US',
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'email' => 'jane@example.com',
        'phone' => '+1.9876543210',
        'address_one' => '456 Oak Ave',
        'city' => 'Springfield',
        'state_province' => 'NY',
        'postal_code' => '67890',
    ]);

    // Create domain price
    DomainPrice::factory()->create([
        'tld' => '.net',
        'register_price' => 1499,
    ]);

    // Mock the Namecheap domain service
    $mockService = $this->mock(DomainRegistrationServiceInterface::class);
    $mockService->shouldReceive('registerDomain')
        ->once()
        ->andReturn([
            'success' => true,
            'domain' => 'example.net',
            'message' => 'Domain registered successfully',
        ]);
    $mockService->shouldReceive('updateNameservers')
        ->once()
        ->andReturn(['success' => true]);

    app()->instance('namecheap_domain_service', $mockService);

    $action = app(RegisterDomainAction::class);

    // Register domain with explicit userId (not authenticated)
    $result = $action->handle(
        'example.net',
        [
            'registrant' => $contact->id,
            'admin' => $contact->id,
            'technical' => $contact->id,
            'billing' => $contact->id,
        ],
        1,
        ['ns1.example.net', 'ns2.example.net'],
        false,
        $user->id // Explicit user ID
    );

    expect($result['success'])->toBeTrue();
    expect($result['domain_id'])->not->toBeNull();

    // Verify domain was created with correct owner_id
    $domain = Domain::query()->find($result['domain_id']);
    expect($domain)->not->toBeNull();
    expect($domain->owner_id)->toBe($user->id);
    expect($domain->owner->id)->toBe($user->id);
});

test('domain registration fails when no user id is provided and no authenticated user', function (): void {
    // Create test country
    Country::factory()->create(['iso_code' => 'US']);

    // Create test user and contact (but don't authenticate)
    $user = User::factory()->create();
    $contact = Contact::factory()->create([
        'user_id' => $user->id,
        'country_code' => 'US',
    ]);

    // Create domain price
    DomainPrice::factory()->create([
        'tld' => '.org',
        'register_price' => 999,
    ]);

    $action = app(RegisterDomainAction::class);

    // Try to register domain without authentication and without explicit userId
    $result = $action->handle(
        'example.org',
        [
            'registrant' => $contact->id,
            'admin' => $contact->id,
            'technical' => $contact->id,
            'billing' => $contact->id,
        ],
        1,
        ['ns1.example.org', 'ns2.example.org']
    );

    // Should fail with appropriate message
    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('No user ID provided');
});
