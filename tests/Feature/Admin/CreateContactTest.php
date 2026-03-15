<?php

declare(strict_types=1);

use App\Actions\CreateContactAction;
use App\Actions\CreateDualProviderContactAction;
use App\Models\Contact;
use App\Models\Country;
use App\Models\User;
use App\Services\Domain\EppDomainService;

it('can store a contact without providing contact_type', function (): void {
    $user = User::factory()->create();
    Country::query()->updateOrCreate(
        ['iso_code' => 'USA'],
        ['iso_alpha2' => 'US', 'name' => 'United States']
    );

    $response = $this->actingAs($user)->post(route('admin.contacts.store'), [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'address_one' => '123 Main St',
        'city' => 'New York',
        'state_province' => 'NY',
        'postal_code' => '10001',
        'country_code' => 'US',
        'phone' => '1234567890',
        'email' => 'john@example.com',
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('admin.contacts.index'));
});

it('passes validation without contact_type', function (): void {
    $user = User::factory()->create();
    Country::query()->updateOrCreate(
        ['iso_code' => 'USA'],
        ['iso_alpha2' => 'US', 'name' => 'United States']
    );

    $response = $this->actingAs($user)->post(route('admin.contacts.store'), [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'address_one' => '456 Oak Ave',
        'city' => 'Boston',
        'state_province' => 'MA',
        'postal_code' => '02101',
        'country_code' => 'US',
        'phone' => '9876543210',
        'email' => 'jane@example.com',
    ]);

    $response->assertSessionDoesntHaveErrors('contact_type');
});

it('saves contact locally when EPP connection fails', function (): void {
    $user = User::factory()->create();
    Country::query()->updateOrCreate(
        ['iso_code' => 'USA'],
        ['iso_alpha2' => 'US', 'name' => 'United States']
    );

    $eppMock = Mockery::mock(EppDomainService::class);
    $eppMock->shouldReceive('createContacts')
        ->once()
        ->andThrow(new Exception('Failed to establish EPP connection: Connection timed out'));

    $this->app->instance(EppDomainService::class, $eppMock);

    $this->app->when(CreateContactAction::class)
        ->needs('$useTestingProviderResults')
        ->give(false);

    $response = $this->actingAs($user)->post(route('admin.contacts.store'), [
        'first_name' => 'Alice',
        'last_name' => 'Smith',
        'address_one' => '789 Pine Rd',
        'city' => 'Chicago',
        'state_province' => 'IL',
        'postal_code' => '60601',
        'country_code' => 'US',
        'phone' => '5551234567',
        'email' => 'alice@example.com',
    ]);

    $response->assertRedirect(route('admin.contacts.index'));
    $response->assertSessionHas('success');

    $contact = Contact::query()->where('email', 'alice@example.com')->first();
    expect($contact)->not->toBeNull();
    expect($contact->first_name)->toBe('Alice');
    expect($contact->last_name)->toBe('Smith');
});
