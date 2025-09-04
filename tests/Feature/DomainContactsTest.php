<?php

use App\Models\Domain;
use App\Models\Contact;
use App\Services\Domain\NamecheapDomainService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->domain = Domain::factory()->create(['name' => 'example.com']);
});

it('saves contacts from namecheap response', function () {
    $mockService = mock(NamecheapDomainService::class);
    $mockService->shouldReceive('getDomainContacts')
        ->once()
        ->with('example.com')
        ->andReturn([
            'CommandResponse' => [
                'DomainContactsResult' => [
                    'Registrant' => [
                        'EmailAddress' => 'test@example.com',
                        'FirstName' => 'John',
                        'LastName' => 'Doe',
                        'OrganizationName' => 'Test Org',
                        'JobTitle' => 'Manager',
                        'Address1' => '123 Test St',
                        'Address2' => 'Suite 100',
                        'City' => 'Test City',
                        'StateProvince' => 'Test State',
                        'PostalCode' => '12345',
                        'Country' => 'US',
                        'Phone' => '1234567890'
                    ]
                ]
            ]
        ]);

    $this->app->instance(NamecheapDomainService::class, $mockService);

    $response = $this->get(route('admin.domains.contacts', $this->domain));

    expect(Contact::count())->toBe(1);

    $contact = Contact::first();
    expect($contact)->email->toBe('test@example.com')
        ->and($contact)->first_name->toBe('John')
        ->and($contact)->last_name->toBe('Doe');

    expect($this->domain->contacts()->count())->toBe(1);
    expect($this->domain->contacts()->first()->pivot->type)->toBe('registrant');
});

it('handles missing contact data gracefully', function () {
    $mockService = mock(NamecheapDomainService::class);
    $mockService->shouldReceive('getDomainContacts')
        ->once()
        ->with('example.com')
        ->andReturn([
            'CommandResponse' => [
                'DomainContactsResult' => [
                    'Registrant' => [
                        'EmailAddress' => 'test@example.com'
                    ]
                ]
            ]
        ]);

    $this->app->instance(NamecheapDomainService::class, $mockService);

    $response = $this->get(route('admin.domains.contacts', $this->domain));

    expect(Contact::count())->toBe(1);
    $contact = Contact::first();
    expect($contact)->email->toBe('test@example.com')
        ->and($contact)->first_name->toBe('')
        ->and($contact)->last_name->toBe('');
});
