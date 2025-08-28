<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\RegisterDomainAction;
use App\Models\Contact;
use App\Models\Country;
use App\Models\DomainPrice;
use App\Models\User;
use App\Services\Domain\DomainRegistrationServiceInterface;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RegisterDomainActionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Contact $contact;
    private DomainPrice $domainPrice;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user
        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        // Create test country
        $country = Country::factory()->create(['iso_code' => 'US']);

        // Create test contact
        $this->contact = Contact::factory()->create([
            'user_id' => $this->user->id,
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
        $this->domainPrice = DomainPrice::factory()->create([
            'tld' => '.com',
            'register_price' => 1299, // $12.99 in cents
        ]);
    }

    /**
     * @throws Exception
     */
    public function test_it_registers_international_domain_successfully(): void
    {
        // Mock the Namecheap domain service
        $mockService = $this->mock(DomainRegistrationServiceInterface::class);

        // Configure the mock to return successful registration
        $mockService->shouldReceive('registerDomain')
            ->once()
            ->andReturn([
                'success' => true,
                'domain' => 'example.com',
                'message' => 'Domain registered successfully',
            ]);

        // Mock nameserver update
        $mockService->shouldReceive('updateNameservers')
            ->once()
            ->andReturn(['success' => true]);

        app()->instance('namecheap_domain_service', $mockService);

        $action = app(RegisterDomainAction::class);

        $result = $action->handle(
            'example.com',
            [
                'registrant' => $this->contact->id,
                'admin' => $this->contact->id,
                'technical' => $this->contact->id,
                'billing' => $this->contact->id,
            ],
            1,
            ['ns1.example.com', 'ns2.example.com']
        );

        $this->assertTrue($result['success']);
        $this->assertEquals('example.com', $result['domain']);
        $this->assertStringContainsString('Namecheap', $result['message']);
    }

    /**
     * @throws Exception
     */
    public function test_it_registers_local_domain_successfully(): void
    {
        // Create EPP contact with contact_id
        $eppContact = Contact::factory()->create([
            'user_id' => $this->user->id,
            'contact_id' => 'EPP123456',
            'country_code' => 'RW',
        ]);

        // Create .rw domain price
        DomainPrice::factory()->create([
            'tld' => '.rw',
            'register_price' => 5000, // 5000 RWF in cents
        ]);

        // Mock the EPP domain service
        $mockService = $this->mock(DomainRegistrationServiceInterface::class);

        // Configure the mock to return successful registration
        $mockService->shouldReceive('registerDomain')
            ->once()
            ->andReturn([
                'success' => true,
                'domain' => 'example.rw',
                'message' => 'Domain registered successfully',
            ]);

        // Mock nameserver update
        $mockService->shouldReceive('updateNameservers')
            ->once()
            ->andReturn(['success' => true]);

        app()->instance('epp_domain_service', $mockService);

        $action = app(RegisterDomainAction::class);

        $result = $action->handle(
            'example.rw',
            [
                'registrant' => $eppContact->id,
                'admin' => $eppContact->id,
                'technical' => $eppContact->id,
                'billing' => $eppContact->id,
            ],
            1,
            ['ns1.example.rw', 'ns2.example.rw']
        );

        $this->assertTrue($result['success']);
        $this->assertEquals('example.rw', $result['domain']);
        $this->assertStringContainsString('EPP', $result['message']);
    }
}
