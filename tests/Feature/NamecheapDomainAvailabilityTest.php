<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\Domain\NamecheapDomainService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class NamecheapDomainAvailabilityTest extends TestCase
{
    private NamecheapDomainService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = resolve(NamecheapDomainService::class);
    }

    public function test_it_correctly_checks_domain_availability(): void
    {
        Http::fake([
            '*' => Http::response('<?xml version="1.0" encoding="utf-8"?>
                <ApiResponse Status="OK" xmlns="http://api.namecheap.com/xml.response">
                    <Errors />
                    <CommandResponse Type="namecheap.domains.check">
                        <DomainCheckResult Domain="mbabazi.net" Available="true" />
                    </CommandResponse>
                </ApiResponse>'
            ),
        ]);

        $result = $this->service->checkAvailability(['mbabazi.net']);

        $this->assertTrue($result['mbabazi.net']['available']);
        $this->assertEmpty($result['mbabazi.net']['reason']);
    }

    public function test_it_handles_unavailable_domains(): void
    {
        Http::fake([
            '*' => Http::response('<?xml version="1.0" encoding="utf-8"?>
                <ApiResponse Status="OK" xmlns="http://api.namecheap.com/xml.response">
                    <Errors />
                    <CommandResponse Type="namecheap.domains.check">
                        <DomainCheckResult Domain="mbabazi.net" Available="false" />
                    </CommandResponse>
                </ApiResponse>'
            ),
        ]);

        $result = $this->service->checkAvailability(['mbabazi.net']);

        $this->assertFalse($result['mbabazi.net']['available']);
        $this->assertEquals('Domain not available', $result['mbabazi.net']['reason']);
    }

    /**
     * @throws ConnectionException
     */
    public function test_it_handles_api_errors(): void
    {
        Http::fake([
            '*' => Http::response('<?xml version="1.0" encoding="utf-8"?>
                <ApiResponse Status="ERROR" xmlns="http://api.namecheap.com/xml.response">
                    <Errors>
                        <Error Number="1">API authentication failed</Error>
                    </Errors>
                </ApiResponse>'
            ),
        ]);

        $result = $this->service->checkAvailability(['mbabazi.net']);

        $this->assertFalse($result['mbabazi.net']['available']);
        $this->assertStringContainsString('Service error', $result['mbabazi.net']['reason']);
    }
}
