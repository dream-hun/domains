<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Domain;

use App\Providers\DomainServiceProvider;
use App\Services\Domain\EppDomainService;
use App\Services\Domain\InternationalDomainService;
use App\Services\Domain\LocalDomainService;
use Exception;
use Tests\TestCase;

final class DomainServiceTest extends TestCase
{
    /**
     * Test the LocalDomainService implementation
     *
     * @throws Exception
     */
    public function test_local_domain_service_implementation(): void
    {
        // Create an instance of the service
        $service = new EppDomainService();

        // Test the checkAvailability method
        $result = $service->checkAvailability(['example.co.ke']);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('available', $result);
        $this->assertArrayHasKey('reason', $result);

        // Test the getDomainInfo method
        $info = $service->getDomainInfo('example.co.ke');
        $this->assertIsArray($info);
        $this->assertArrayHasKey('success', $info);

        // Test the registerDomain method
        $register = $service->registerDomain('example.co.ke', [], 1);
        $this->assertIsArray($register);
        $this->assertArrayHasKey('success', $register);
    }

    /**
     * Test the InternationalDomainService implementation
     */
    public function test_international_domain_service_implementation(): void
    {
        // Create an instance of the service
        $service = new InternationalDomainService();

        // Test the checkAvailability method
        $result = $service->checkAvailability(['example.com']);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('available', $result);
        $this->assertArrayHasKey('reason', $result);

        // Test the getDomainInfo method
        $info = $service->getDomainInfo('example.com');
        $this->assertIsArray($info);
        $this->assertArrayHasKey('success', $info);

        // Test the registerDomain method
        $register = $service->registerDomain('example.com', [], 1);
        $this->assertIsArray($register);
        $this->assertArrayHasKey('success', $register);
    }

    /**
     * Test the LocalDomainService can be resolved directly
     */
    public function test_local_domain_service_can_be_resolved(): void
    {
        // Create the provider
        $provider = new DomainServiceProvider($this->app);

        // Register the services
        $provider->register();

        // Resolve the service directly
        $service = $this->app->make(LocalDomainService::class);
        $this->assertInstanceOf(LocalDomainService::class, $service);
    }

    /**
     * Test the InternationalDomainService can be resolved directly
     */
    public function test_international_domain_service_can_be_resolved(): void
    {
        // Create the provider
        $provider = new DomainServiceProvider($this->app);

        // Register the services
        $provider->register();

        // Resolve the service directly
        $service = $this->app->make(InternationalDomainService::class);
        $this->assertInstanceOf(InternationalDomainService::class, $service);
    }
}
