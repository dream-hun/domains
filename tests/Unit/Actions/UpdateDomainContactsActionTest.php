<?php

declare(strict_types=1);

use App\Actions\Domains\UpdateDomainContactsAction;
use App\Models\Contact;
use App\Models\Domain;
use App\Models\User;
use App\Services\Domain\DomainServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->domain = Domain::factory()->create(['owner_id' => $this->user->id]);
    $this->contacts = Contact::factory()->count(4)->create(['user_id' => $this->user->id]);

    $this->domainService = $this->mock(DomainServiceInterface::class);
    $this->action = new UpdateDomainContactsAction($this->domainService);
});

it('successfully updates domain contacts', function (): void {
    $this->actingAs($this->user);

    $contactIds = [
        'registrant' => ['contact_id' => $this->contacts[0]->id],
        'admin' => ['contact_id' => $this->contacts[1]->id],
        'technical' => ['contact_id' => $this->contacts[2]->id],
        'billing' => ['contact_id' => $this->contacts[3]->id],
    ];

    $expectedContactInfo = [
        'registrant' => [
            'first_name' => $this->contacts[0]->first_name,
            'last_name' => $this->contacts[0]->last_name,
            'organization' => $this->contacts[0]->organization,
            'email' => $this->contacts[0]->email,
            'phone' => $this->contacts[0]->phone,
            'address_one' => $this->contacts[0]->address_one,
            'address_two' => $this->contacts[0]->address_two,
            'city' => $this->contacts[0]->city,
            'state_province' => $this->contacts[0]->state_province,
            'postal_code' => $this->contacts[0]->postal_code,
            'country_code' => $this->contacts[0]->country_code,
        ],
        'admin' => [
            'first_name' => $this->contacts[1]->first_name,
            'last_name' => $this->contacts[1]->last_name,
            'organization' => $this->contacts[1]->organization,
            'email' => $this->contacts[1]->email,
            'phone' => $this->contacts[1]->phone,
            'address_one' => $this->contacts[1]->address_one,
            'address_two' => $this->contacts[1]->address_two,
            'city' => $this->contacts[1]->city,
            'state_province' => $this->contacts[1]->state_province,
            'postal_code' => $this->contacts[1]->postal_code,
            'country_code' => $this->contacts[1]->country_code,
        ],
        'technical' => [
            'first_name' => $this->contacts[2]->first_name,
            'last_name' => $this->contacts[2]->last_name,
            'organization' => $this->contacts[2]->organization,
            'email' => $this->contacts[2]->email,
            'phone' => $this->contacts[2]->phone,
            'address_one' => $this->contacts[2]->address_one,
            'address_two' => $this->contacts[2]->address_two,
            'city' => $this->contacts[2]->city,
            'state_province' => $this->contacts[2]->state_province,
            'postal_code' => $this->contacts[2]->postal_code,
            'country_code' => $this->contacts[2]->country_code,
        ],
        'billing' => [
            'first_name' => $this->contacts[3]->first_name,
            'last_name' => $this->contacts[3]->last_name,
            'organization' => $this->contacts[3]->organization,
            'email' => $this->contacts[3]->email,
            'phone' => $this->contacts[3]->phone,
            'address_one' => $this->contacts[3]->address_one,
            'address_two' => $this->contacts[3]->address_two,
            'city' => $this->contacts[3]->city,
            'state_province' => $this->contacts[3]->state_province,
            'postal_code' => $this->contacts[3]->postal_code,
            'country_code' => $this->contacts[3]->country_code,
        ],
    ];

    $this->domainService
        ->shouldReceive('updateDomainContacts')
        ->once()
        ->with($this->domain->name, $expectedContactInfo)
        ->andReturn(['success' => true, 'message' => 'Contacts updated successfully']);

    $result = $this->action->handle($this->domain, $contactIds);

    expect($result)->toBe([
        'success' => true,
        'message' => 'Contacts updated successfully',
    ]);

    // Verify contacts were synced to the domain
    $this->domain->refresh();
    expect($this->domain->contacts)->toHaveCount(4);
});

it('handles domain service failure', function (): void {
    $this->actingAs($this->user);

    $contactIds = [
        'registrant' => ['contact_id' => $this->contacts[0]->id],
        'admin' => ['contact_id' => $this->contacts[1]->id],
        'technical' => ['contact_id' => $this->contacts[2]->id],
        'billing' => ['contact_id' => $this->contacts[3]->id],
    ];

    $this->domainService
        ->shouldReceive('updateDomainContacts')
        ->once()
        ->andReturn(['success' => false, 'message' => 'Failed to update contacts']);

    $result = $this->action->handle($this->domain, $contactIds);

    expect($result)->toBe([
        'success' => false,
        'message' => 'Failed to update contacts',
    ]);

    // Verify contacts were not synced to the domain
    $this->domain->refresh();
    expect($this->domain->contacts)->toHaveCount(0);
});

it('handles exceptions gracefully', function (): void {
    $this->actingAs($this->user);

    $contactIds = [
        'registrant' => ['contact_id' => 99999], // Non-existent contact
    ];

    $result = $this->action->handle($this->domain, $contactIds);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('Failed to update domain contacts');
});

it('prepares contact data correctly', function (): void {
    $this->actingAs($this->user);

    $contactIds = [
        'registrant' => ['contact_id' => $this->contacts[0]->id],
    ];

    $this->domainService
        ->shouldReceive('updateDomainContacts')
        ->once()
        ->with($this->domain->name, Mockery::on(fn (array $contactInfo): bool => isset($contactInfo['registrant']) &&
               $contactInfo['registrant']['first_name'] === $this->contacts[0]->first_name &&
               $contactInfo['registrant']['email'] === $this->contacts[0]->email))
        ->andReturn(['success' => true]);

    $this->action->handle($this->domain, $contactIds);
});
