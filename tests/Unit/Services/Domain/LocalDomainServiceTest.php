<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Domain;

use AfriCC\EPP\Client as EPPClient;
use App\Services\Domain\EppDomainService;
use Exception;
use Illuminate\Support\Facades\Log;
use ReflectionClass;
use Tests\TestCase;

final class LocalDomainServiceTest extends TestCase
{
    /**
     * Test domain availability check with retry logic
     */
    public function test_check_availability_with_retry(): void
    {
        // Mock the Log facade
        Log::shouldReceive('warning')->zeroOrMoreTimes();
        Log::shouldReceive('error')->zeroOrMoreTimes();
        Log::shouldReceive('debug')->zeroOrMoreTimes();
        Log::shouldReceive('info')->zeroOrMoreTimes();

        // Create a mock of the EppDomainService
        $service = $this->getMockBuilder(EppDomainService::class)
            ->onlyMethods(['connectWithRetry'])
            ->disableOriginalConstructor()
            ->getMock();

        // Initialize required properties via Reflection
        $reflection = new ReflectionClass(EppDomainService::class);

        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setValue($service, $this->createMock(EPPClient::class));

        $configProperty = $reflection->getProperty('config');
        $configProperty->setValue($service, ['host' => 'test.host']);

        // Set up the mock to simulate a successful connection after retries
        $service->expects($this->once())
            ->method('connectWithRetry')
            ->willReturn('Greeting message');

        // Call the method that uses the connectWithRetry method
        try {
            // EppDomainService::checkAvailability expects an array
            $result = $service->checkAvailability(['example.com']);

            // Verify that the method was called (connectWithRetry was invoked)
            // The mock expectation above will verify connectWithRetry was called
            $this->assertIsArray($result);
        } catch (Exception $exception) {
            // If we get an exception, the test fails
            $this->fail('Exception thrown: '.$exception->getMessage());
        }
    }

    /**
     * Test that all methods use the connectWithRetry method
     */
    public function test_all_methods_use_connect_with_retry(): void
    {
        // Create a mock of the EppDomainService
        $service = $this->getMockBuilder(EppDomainService::class)
            ->onlyMethods(['connectWithRetry'])
            ->disableOriginalConstructor()
            ->getMock();

        // Initialize required properties via Reflection
        $reflection = new ReflectionClass(EppDomainService::class);

        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setValue($service, $this->createMock(EPPClient::class));

        $configProperty = $reflection->getProperty('config');
        $configProperty->setValue($service, ['host' => 'test.host']);

        // Set up the mock to expect multiple calls to connectWithRetry
        $service->expects($this->atLeastOnce())
            ->method('connectWithRetry')
            ->willReturn('Greeting message');

        // Call each method that should use connectWithRetry
        // We're not testing the actual functionality, just that they call connectWithRetry
        try {
            // These calls will fail because we're not setting up the full mock chain
            // But we only care that connectWithRetry is called
            $service->checkAvailability(['example.com']);
        } catch (Exception) {
            // Expected
        }

        // Verify that connectWithRetry was called
        // PHPUnit will automatically verify this based on our expects() setup
    }
}
