<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\RegisterDomainAction;
use App\Services\Domain\DomainRegistrationServiceInterface;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RegisterDomainActionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @throws Exception
     */
    public function test_it_checks_domain_availability_before_registration(): void
    {
        // Mock the Namecheap domain service
        $mockService = $this->mock(DomainRegistrationServiceInterface::class);

        // Configure the mock to return domain as unavailable
        $mockService->shouldReceive('checkAvailability')
            ->with(['example.com'])
            ->once()
            ->andReturn(['example.com' => (object) ['available' => false]]);

        // Mock shouldn't receive registerDomain call since domain is unavailable
        $mockService->shouldNotReceive('registerDomain');

        app()->instance('namecheap_domain_service', $mockService);

        $action = app(RegisterDomainAction::class);

        $result = $action->handle(
            'example.com',
            [
                'registrant' => [
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'email' => 'john@example.com',
                    'phone' => '+1.1234567890',
                    'address_one' => '123 Main St',
                    'city' => 'Anytown',
                    'state_province' => 'CA',
                    'postal_code' => '12345',
                    'country_code' => 'US',
                ],
            ],
            1
        );

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not available', $result['message']);
    }
}
