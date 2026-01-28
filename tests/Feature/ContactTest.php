<?php

declare(strict_types=1);

use App\Enums\ContactType;
use App\Models\Contact;
use App\Models\Country;
use App\Models\User;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

function validContactData(?Country $country = null): array
{
    $country ??= Country::factory()->create();

    return [
        'contact_type' => ContactType::Registrant->value,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'title' => 'CEO',
        'organization' => 'Test Company',
        'address_one' => '123 Main St',
        'address_two' => 'Suite 100',
        'city' => 'New York',
        'state_province' => 'NY',
        'postal_code' => '10001',
        'country_code' => $country->iso_code,
        'phone' => '+1-555-123-4567',
        'phone_extension' => '123',
        'fax_number' => '+1-555-123-4568',
        'email' => 'john.doe@example.com',
    ];
}

test('can view contacts index page', function (): void {
    Contact::factory(3)->create(['user_id' => $this->user->id]);

    $response = $this->get(route('admin.contacts.index'));

    $response->assertSuccessful()
        ->assertViewIs('admin.contacts.index')
        ->assertViewHas('contacts');
});

test('can search and filter contacts', function (): void {
    $contact1 = Contact::factory()->create([
        'first_name' => 'John',
        'last_name' => 'Doe',
        'contact_type' => ContactType::Registrant,
        'user_id' => $this->user->id,
    ]);

    $contact2 = Contact::factory()->create([
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'contact_type' => ContactType::Technical,
        'user_id' => $this->user->id,
    ]);

    // Search by name
    $response = $this->get(route('admin.contacts.index', ['search' => 'John']));
    $response->assertSuccessful()
        ->assertSee('John Doe')
        ->assertDontSee('Jane Smith');

    // Filter by contact type
    $response = $this->get(route('admin.contacts.index', ['contact_type' => ContactType::Technical->value]));
    $response->assertSuccessful()
        ->assertSee('Jane Smith')
        ->assertDontSee('John Doe');
});

test('can view create contact page', function (): void {
    Country::factory()->create(['name' => 'United States']);

    $response = $this->get(route('admin.contacts.create'));

    $response->assertSuccessful()
        ->assertViewIs('admin.contacts.create')
        ->assertViewHas('countries');
});

test('can create contact', function (): void {
    $country = Country::factory()->create();
    $contactData = validContactData($country);

    $response = $this->post(route('admin.contacts.store'), $contactData);

    $response->assertRedirect(route('admin.contacts.index'))
        ->assertSessionHas('success', 'Contact created successfully in both EPP registry and local database.');

    // Verify contact was created
    expect(Contact::query()->count())->toBe(1);

    $contact = Contact::query()->first();
    expect($contact->first_name)->toBe('John')
        ->and($contact->last_name)->toBe('Doe')
        ->and($contact->email)->toBe('john.doe@example.com')
        ->and($contact->contact_type)->toBe(ContactType::Registrant)
        ->and($contact->user_id)->toBe($this->user->id)
        ->and($contact->country_code)->toBe($country->iso_code);
});

test('validates required fields when creating contact', function (): void {
    $response = $this->post(route('admin.contacts.store'), []);

    $response->assertSessionHasErrors([
        'contact_type',
        'first_name',
        'last_name',
        'address_one',
        'city',
        'state_province',
        'postal_code',
        'country_code',
        'phone',
        'email',
    ]);
});

test('validates enum values for contact type', function (): void {
    $response = $this->post(route('admin.contacts.store'), [
        'contact_type' => 'invalid_type',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'address_one' => '123 Main St',
        'city' => 'New York',
        'state_province' => 'NY',
        'postal_code' => '10001',
        'country_code' => 'US',
        'phone' => '+1-555-123-4567',
        'email' => 'john.doe@example.com',
    ]);

    $response->assertSessionHasErrors(['contact_type']);
});

test('can view contact details', function (): void {
    $contact = Contact::factory()->create([
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john.doe@example.com',
        'user_id' => $this->user->id,
    ]);

    $response = $this->get(route('admin.contacts.show', $contact));

    $response->assertSuccessful()
        ->assertViewIs('admin.contacts.show')
        ->assertViewHas('contact', $contact)
        ->assertSee($contact->first_name)
        ->assertSee($contact->last_name)
        ->assertSee($contact->email);
});

test('can view edit contact page', function (): void {
    $contact = Contact::factory()->create(['user_id' => $this->user->id]);
    Country::factory()->create(['name' => 'United States']);

    $response = $this->get(route('admin.contacts.edit', $contact));

    $response->assertSuccessful()
        ->assertViewIs('admin.contacts.edit')
        ->assertViewHas('contact', $contact)
        ->assertViewHas('countries');
});

test('can update contact', function (): void {
    $contact = Contact::factory()->create([
        'first_name' => 'John',
        'email' => 'john@example.com',
        'user_id' => $this->user->id,
    ]);

    $updateData = [
        'contact_type' => ContactType::Technical->value,
        'first_name' => 'Jane',
        'last_name' => $contact->last_name,
        'address_one' => $contact->address_one,
        'city' => $contact->city,
        'state_province' => $contact->state_province,
        'postal_code' => $contact->postal_code,
        'phone' => $contact->phone,
        'email' => 'jane@example.com',
    ];

    $response = $this->put(route('admin.contacts.update', $contact), $updateData);

    $response->assertRedirect(route('admin.contacts.show', $contact))
        ->assertSessionHas('success', 'Contact updated successfully in both EPP registry and local database');

    $contact->refresh();
    expect($contact->first_name)->toBe('Jane')
        ->and($contact->email)->toBe('jane@example.com')
        ->and($contact->contact_type)->toBe(ContactType::Technical);
});

test('can delete contact', function (): void {
    $contact = Contact::factory()->create(['user_id' => $this->user->id]);

    $response = $this->delete(route('admin.contacts.destroy', $contact));

    $response->assertRedirect(route('admin.contacts.index'))
        ->assertSessionHas('success', 'Contact deleted successfully.');

    expect(Contact::query()->find($contact->id))->toBeNull();
});

test('requires authentication for all contact routes', function (): void {
    auth()->logout();

    $contact = Contact::factory()->create();

    $this->get(route('admin.contacts.index'))->assertRedirect('/login');
    $this->get(route('admin.contacts.create'))->assertRedirect('/login');
    $this->post(route('admin.contacts.store'))->assertRedirect('/login');
    $this->get(route('admin.contacts.show', $contact))->assertRedirect('/login');
    $this->get(route('admin.contacts.edit', $contact))->assertRedirect('/login');
    $this->put(route('admin.contacts.update', $contact))->assertRedirect('/login');
    $this->delete(route('admin.contacts.destroy', $contact))->assertRedirect('/login');
});
