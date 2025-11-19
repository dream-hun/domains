<?php

declare(strict_types=1);

use App\Models\Contact;
use App\Models\Domain;
use App\Models\User;
use App\Services\Domain\NamecheapDomainService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create(['email_verified_at' => now()]);
    $this->domain = Domain::factory()->create([
        'name' => 'example.com',
        'owner_id' => $this->user->id,
    ]);
});

it('saves contacts from namecheap response', function (): void {
    $mockService = mock(NamecheapDomainService::class);
    $mockService->shouldReceive('getDomainContacts')
        ->once()
        ->with('example.com')
        ->andReturn([
            'success' => true,
            'contacts' => [
                'registrant' => [
                    'email' => 'test@example.com',
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'organization' => 'Test Org',
                    'address_one' => '123 Test St',
                    'address_two' => 'Suite 100',
                    'city' => 'Test City',
                    'state_province' => 'Test State',
                    'postal_code' => '12345',
                    'country_code' => 'US',
                    'phone' => '1234567890',
                ],
            ],
        ]);

    $this->app->instance(NamecheapDomainService::class, $mockService);

    $response = $this->actingAs($this->user)
        ->post(route('admin.domain.fetchContacts', $this->domain->uuid));

    $response->assertRedirect()
        ->assertSessionHas('success');

    expect(Contact::query()->count())->toBe(1);

    $contact = Contact::query()->first();
    expect($contact)->email->toBe('test@example.com')
        ->and($contact)->first_name->toBe('John')
        ->and($contact)->last_name->toBe('Doe');

    expect($this->domain->contacts()->count())->toBe(1);
    expect($this->domain->contacts()->first()->pivot->type)->toBe('registrant');
});

it('handles missing contact data gracefully', function (): void {
    $mockService = mock(NamecheapDomainService::class);
    $mockService->shouldReceive('getDomainContacts')
        ->once()
        ->with('example.com')
        ->andReturn([
            'success' => true,
            'contacts' => [
                'registrant' => [
                    'email' => 'test@example.com',
                ],
            ],
        ]);

    $this->app->instance(NamecheapDomainService::class, $mockService);

    $response = $this->actingAs($this->user)
        ->post(route('admin.domain.fetchContacts', $this->domain->uuid));

    $response->assertRedirect()
        ->assertSessionHas('success');

    expect(Contact::query()->count())->toBe(1);
    $contact = Contact::query()->first();
    expect($contact)->email->toBe('test@example.com')
        ->and($contact)->first_name->toBe('')
        ->and($contact)->last_name->toBe('');
});
