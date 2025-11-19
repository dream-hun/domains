<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\CreateContactAction;
use App\Actions\CreateDomainContactAction;
use App\Actions\CreateDualProviderContactAction;
use App\Actions\UpdateContactAction;
use App\Models\Contact;
use App\Models\Country;
use App\Models\User;
use App\Services\Domain\EppDomainService;
use App\Services\Domain\InternationalDomainService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

final class ContactActionsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Country $country;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->country = Country::factory()->create();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_update_contact_action_updates_contact_successfully(): void
    {
        // Create a contact first
        $contact = Contact::factory()->create([
            'contact_id' => 'CON12345678',
            'user_id' => $this->user->id,
        ]);

        // Create the action with real EPP service
        $action = new UpdateContactAction(app(EppDomainService::class));

        $updateData = [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane.smith@example.com',
            'phone' => '+250987654321',
            'address_one' => '456 Oak St',
            'city' => 'Kigali',
            'state_province' => 'Kigali',
            'postal_code' => '54321',
            'country_code' => 'RW',
        ];

        $result = $action->handle($contact, $updateData);

        // Verify the update was successful
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Contact updated successfully', $result['message']);

        // Verify the contact was updated in the database
        $contact->refresh();
        $this->assertEquals('Jane', $contact->first_name);
        $this->assertEquals('Smith', $contact->last_name);
        $this->assertEquals('jane.smith@example.com', $contact->email);
    }

    public function test_update_contact_action_works_without_contact_id(): void
    {
        // Create a contact without contact_id (local only)
        $contact = Contact::factory()->create([
            'contact_id' => null,
            'user_id' => $this->user->id,
        ]);

        // Create the action with real EPP service
        $action = new UpdateContactAction(app(EppDomainService::class));

        $updateData = [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane.smith@example.com',
        ];

        $result = $action->handle($contact, $updateData);

        // Verify the update was successful (local only)
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Contact updated successfully in local database', $result['message']);

        // Verify the contact was updated in the database
        $contact->refresh();
        $this->assertEquals('Jane', $contact->first_name);
        $this->assertEquals('Smith', $contact->last_name);
        $this->assertEquals('jane.smith@example.com', $contact->email);
    }

    public function test_create_contact_action_creates_contacts_in_both_providers(): void
    {
        // Mock the EPP and Namecheap services
        $eppService = Mockery::mock(EppDomainService::class);
        $namecheapService = Mockery::mock(InternationalDomainService::class);

        // Mock EPP service response
        $eppService->shouldReceive('createContacts')
            ->once()
            ->andReturn([
                'contact_id' => 'CON12345678',
                'auth' => 'auth123',
                'code' => 1000,
                'message' => 'Contact created successfully',
            ]);

        // Mock Namecheap service response
        $namecheapService->shouldReceive('createContact')
            ->once()
            ->andReturn(Contact::factory()->make([
                'contact_id' => 'NC87654321',
            ]));

        // Create the action with mocked services
        $action = new CreateContactAction(
            new CreateDualProviderContactAction($eppService, $namecheapService),
            false
        );

        $contactData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'phone' => '+250123456789',
            'address_one' => '123 Main St',
            'city' => 'Kigali',
            'state_province' => 'Kigali',
            'postal_code' => '12345',
            'country_id' => $this->country->id,
            'user_id' => $this->user->id,
        ];

        $result = $action->handle($contactData);

        // Verify both contacts were created
        $this->assertArrayHasKey('epp', $result);
        $this->assertArrayHasKey('namecheap', $result);

        $this->assertEquals('CON12345678', $result['epp']->contact_id);
        $this->assertEquals('NC87654321', $result['namecheap']->contact_id);

        $this->assertEquals('epp', $result['epp']->provider);
        $this->assertEquals('namecheap', $result['namecheap']->provider);
    }

    public function test_create_domain_contact_action_creates_contacts_for_domain(): void
    {
        // Mock the EPP and Namecheap services
        $eppService = Mockery::mock(EppDomainService::class);
        $namecheapService = Mockery::mock(InternationalDomainService::class);

        // Mock EPP service response
        $eppService->shouldReceive('createContacts')
            ->once()
            ->andReturn([
                'contact_id' => 'CON12345678',
                'auth' => 'auth123',
                'code' => 1000,
                'message' => 'Contact created successfully',
            ]);

        // Mock Namecheap service response
        $namecheapService->shouldReceive('createContact')
            ->once()
            ->andReturn(Contact::factory()->make([
                'contact_id' => 'NC87654321',
            ]));

        // Create the action with mocked services
        $action = new CreateDomainContactAction($eppService, $namecheapService);

        $contactData = [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane.smith@example.com',
            'phone' => '+250123456789',
            'address_one' => '456 Oak Ave',
            'city' => 'Kigali',
            'state_province' => 'Kigali',
            'postal_code' => '54321',
            'country_id' => $this->country->id,
            'user_id' => $this->user->id,
        ];

        $domain = 'example.co.ke';
        $contactType = 'registrant';

        $result = $action->handle($contactData, $domain, $contactType);

        // Verify both contacts were created
        $this->assertArrayHasKey('epp', $result);
        $this->assertArrayHasKey('namecheap', $result);

        $this->assertEquals('CON12345678', $result['epp']->contact_id);
        $this->assertEquals('NC87654321', $result['namecheap']->contact_id);

        $this->assertEquals('epp', $result['epp']->provider);
        $this->assertEquals('namecheap', $result['namecheap']->provider);

        $this->assertEquals($domain, $result['epp']->domain);
        $this->assertEquals($domain, $result['namecheap']->domain);

        $this->assertEquals($contactType, $result['epp']->contact_type);
        $this->assertEquals($contactType, $result['namecheap']->contact_type);
    }

    public function test_create_contact_action_handles_epp_failure(): void
    {
        // Mock the EPP service to throw an exception
        $eppService = Mockery::mock(EppDomainService::class);
        $namecheapService = Mockery::mock(InternationalDomainService::class);

        $eppService->shouldReceive('createContacts')
            ->once()
            ->andThrow(new Exception('EPP connection failed'));

        // Create the action with mocked services
        $action = new CreateContactAction(
            new CreateDualProviderContactAction($eppService, $namecheapService),
            false
        );

        $contactData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'phone' => '+250123456789',
            'address_one' => '123 Main St',
            'city' => 'Kigali',
            'state_province' => 'Kigali',
            'postal_code' => '12345',
            'country_id' => $this->country->id,
            'user_id' => $this->user->id,
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Failed to create contact in EPP: EPP connection failed');

        $action->handle($contactData);
    }
}
