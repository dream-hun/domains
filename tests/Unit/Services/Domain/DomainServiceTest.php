<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Domain;

use App\Services\Domain\EppDomainService;
use App\Services\Domain\InternationalDomainService;
use Exception;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
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
        $this->configureNamecheapConfig();
        $this->fakeNamecheapResponses();

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
        $service = $this->app->make(EppDomainService::class);

        $this->assertInstanceOf(EppDomainService::class, $service);
    }

    /**
     * Test the InternationalDomainService can be resolved directly
     */
    public function test_international_domain_service_can_be_resolved(): void
    {
        $service = $this->app->make(InternationalDomainService::class);

        $this->assertInstanceOf(InternationalDomainService::class, $service);
    }

    private function configureNamecheapConfig(): void
    {
        config()->set('services.namecheap.apiUser', 'testing-user');
        config()->set('services.namecheap.apiKey', 'testing-key');
        config()->set('services.namecheap.username', 'testing-username');
        config()->set('services.namecheap.client', '127.0.0.1');
        config()->set('services.namecheap.apiBaseUrl', 'https://api.sandbox.namecheap.com/xml.response');
    }

    private function fakeNamecheapResponses(): void
    {
        Http::fake(function (Request $request) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return match ($query['Command'] ?? '') {
                'namecheap.domains.check' => Http::response($this->fakeDomainCheckResponse()),
                'namecheap.domains.getInfo' => Http::response($this->fakeDomainInfoResponse()),
                'namecheap.domains.contacts.create' => Http::response($this->fakeContactCreateResponse()),
                'namecheap.domains.create' => Http::response($this->fakeDomainCreateResponse()),
                default => Http::response($this->fakeDomainCheckResponse()),
            };
        });
    }

    private function fakeDomainCheckResponse(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<ApiResponse Status="OK">
    <CommandResponse Type="namecheap.domains.check">
        <DomainCheckResult Domain="example.com" Available="true" Description="Domain is available" />
    </CommandResponse>
    <Errors />
    <Warnings />
</ApiResponse>
XML;
    }

    private function fakeDomainInfoResponse(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<ApiResponse Status="OK">
    <CommandResponse Type="namecheap.domains.getInfo">
        <DomainGetInfoResult Name="example.com" Status="ACTIVE" Registrant="John Doe" CreatedDate="2020-01-01" ExpiredDate="2030-01-01" />
    </CommandResponse>
    <Errors />
    <Warnings />
</ApiResponse>
XML;
    }

    private function fakeContactCreateResponse(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<ApiResponse Status="OK">
    <CommandResponse Type="namecheap.domains.contacts.create">
        <ContactCreateResult ContactID="12345" />
    </CommandResponse>
    <Errors />
    <Warnings />
</ApiResponse>
XML;
    }

    private function fakeDomainCreateResponse(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<ApiResponse Status="OK">
    <CommandResponse Type="namecheap.domains.create">
        <DomainCreateResult Domain="example.com" OrderId="98765" />
    </CommandResponse>
    <Errors />
    <Warnings />
</ApiResponse>
XML;
    }
}
