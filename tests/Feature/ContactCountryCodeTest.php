<?php

declare(strict_types=1);

use App\Livewire\Checkout\ContactCreateModal;
use App\Models\Contact;
use App\Models\Country;
use App\Models\User;
use Livewire\Livewire;

it('uppercases country_code via model mutator', function (): void {
    $contact = Contact::factory()->create(['country_code' => 'rw']);

    expect($contact->country_code)->toBe('RW');
});

it('uppercases mixed-case country_code via model mutator', function (): void {
    $contact = Contact::factory()->create(['country_code' => 'Rw']);

    expect($contact->country_code)->toBe('RW');
});

it('preserves already uppercase country_code', function (): void {
    $contact = Contact::factory()->create(['country_code' => 'US']);

    expect($contact->country_code)->toBe('US');
});

it('saves contact with valid uppercase country code from modal', function (): void {
    Country::query()->updateOrCreate(
        ['iso_code' => 'RWA'],
        ['iso_alpha2' => 'RW', 'name' => 'Rwanda']
    );

    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(ContactCreateModal::class)
        ->call('openModal')
        ->set('first_name', 'John')
        ->set('last_name', 'Doe')
        ->set('email', 'john@example.com')
        ->set('phone', '0788123456')
        ->set('organization', 'Test Org')
        ->set('address_one', '123 Main St')
        ->set('city', 'Kigali')
        ->set('state_province', 'Kigali')
        ->set('postal_code', '00000')
        ->set('country_code', 'RW')
        ->call('save')
        ->assertDispatched('contact-created');

    $contact = Contact::query()->where('email', 'john@example.com')->firstOrFail();
    expect($contact->country_code)->toBe('RW');
});

it('rejects invalid country code in modal', function (): void {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(ContactCreateModal::class)
        ->call('openModal')
        ->set('first_name', 'John')
        ->set('last_name', 'Doe')
        ->set('email', 'john@example.com')
        ->set('phone', '0788123456')
        ->set('address_one', '123 Main St')
        ->set('city', 'Kigali')
        ->set('state_province', 'Kigali')
        ->set('postal_code', '00000')
        ->set('country_code', 'XX')
        ->call('save')
        ->assertHasErrors(['country_code']);
});
