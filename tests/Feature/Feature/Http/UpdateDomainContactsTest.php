<?php

declare(strict_types=1);

use App\Models\Contact;
use App\Models\Domain;
use App\Models\User;
use App\Services\Domain\DomainServiceInterface;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->domain = Domain::factory()->create(['owner_id' => $this->user->id]);

    // Create contacts for testing
    $this->contacts = Contact::factory()->count(4)->create(['user_id' => $this->user->id]);

    // Mock the domain service
    $this->domainService = $this->mock(DomainServiceInterface::class);
});

it('allows authorized users to update domain contacts', function (): void {
    $this->domainService
        ->shouldReceive('updateDomainContacts')
        ->once()
        ->with($this->domain->name, Mockery::type('array'))
        ->andReturn(['success' => true, 'message' => 'Contacts updated successfully']);

    $contactData = [
        'registrant' => ['contact_id' => $this->contacts[0]->id],
        'admin' => ['contact_id' => $this->contacts[1]->id],
        'technical' => ['contact_id' => $this->contacts[2]->id],
        'billing' => ['contact_id' => $this->contacts[3]->id],
    ];

    $response = $this->actingAs($this->user)
        ->put(route('admin.domains.contacts.update', $this->domain->uuid), $contactData);

    $response->assertRedirect()
        ->assertSessionHas('success', 'Domain contacts updated successfully');

    // Verify contacts were synced to the domain
    $this->domain->refresh();
    expect($this->domain->contacts)->toHaveCount(4);
});

it('prevents unauthorized users from updating domain contacts', function (): void {
    $otherUser = User::factory()->create();

    $contactData = [
        'registrant' => ['contact_id' => $this->contacts[0]->id],
        'admin' => ['contact_id' => $this->contacts[1]->id],
        'technical' => ['contact_id' => $this->contacts[2]->id],
        'billing' => ['contact_id' => $this->contacts[3]->id],
    ];

    $response = $this->actingAs($otherUser)
        ->put(route('admin.domains.contacts.update', $this->domain->uuid), $contactData);

    $response->assertForbidden();
});

it('validates required contact fields', function (): void {
    $response = $this->actingAs($this->user)
        ->put(route('admin.domains.contacts.update', $this->domain->uuid), []);

    $response->assertSessionHasErrors([
        'contacts' => 'Please provide at least one contact to update.',
    ]);
});

it('validates contact existence', function (): void {
    $contactData = [
        'registrant' => ['contact_id' => 999999],
        'admin' => ['contact_id' => 999998],
        'technical' => ['contact_id' => 999997],
        'billing' => ['contact_id' => 999996],
    ];

    $response = $this->actingAs($this->user)
        ->put(route('admin.domains.contacts.update', $this->domain->uuid), $contactData);

    $response->assertSessionHasErrors([
        'registrant.contact_id',
        'admin.contact_id',
        'technical.contact_id',
        'billing.contact_id',
    ]);
});

it('handles domain service failures gracefully', function (): void {
    $this->domainService
        ->shouldReceive('updateDomainContacts')
        ->once()
        ->andReturn(['success' => false, 'message' => 'Failed to update contacts']);

    $contactData = [
        'registrant' => ['contact_id' => $this->contacts[0]->id],
        'admin' => ['contact_id' => $this->contacts[1]->id],
        'technical' => ['contact_id' => $this->contacts[2]->id],
        'billing' => ['contact_id' => $this->contacts[3]->id],
    ];

    $response = $this->actingAs($this->user)
        ->put(route('admin.domains.contacts.update', $this->domain->uuid), $contactData);

    $response->assertRedirect()
        ->assertSessionHasErrors(['error' => 'Failed to update contacts']);
});

it('requires domain edit permission', function (): void {
    $user = User::factory()->create();

    $contactData = [
        'registrant' => ['contact_id' => $this->contacts[0]->id],
        'admin' => ['contact_id' => $this->contacts[1]->id],
        'technical' => ['contact_id' => $this->contacts[2]->id],
        'billing' => ['contact_id' => $this->contacts[3]->id],
    ];

    $response = $this->actingAs($user)
        ->put(route('admin.domains.contacts.update', $this->domain->uuid), $contactData);

    $response->assertForbidden();
});
