<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Domain;

use App\Services\Domain\NamecheapDomainService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class NamecheapDomainReactivationTest extends TestCase
{
    private NamecheapDomainService $service;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.namecheap.apiUser' => 'testuser',
            'services.namecheap.apiKey' => 'testkey',
            'services.namecheap.username' => 'testuser',
            'services.namecheap.client' => '192.168.1.1',
            'services.namecheap.apiBaseUrl' => 'https://api.sandbox.namecheap.com/xml.response',
        ]);

        $this->service = new NamecheapDomainService();
    }

    public function test_reactivates_domain_successfully(): void
    {
        $successfulResponse = '<?xml version="1.0" encoding="UTF-8"?>
        <ApiResponse xmlns="http://api.namecheap.com/xml.response" Status="OK">
            <Errors />
            <Warnings />
            <RequestedCommand>namecheap.domains.reactivate</RequestedCommand>
            <CommandResponse Type="namecheap.domains.reactivate">
                <DomainReactivateResult Domain="test.com" IsSuccess="true" ChargedAmount="15.00" OrderID="12345" TransactionID="67890" />
            </CommandResponse>
            <Server>SERVER-NAME</Server>
            <GMTTimeDifference>+0:00</GMTTimeDifference>
            <ExecutionTime>1.234</ExecutionTime>
        </ApiResponse>';

        Http::fake([
            '*' => Http::response($successfulResponse, 200),
        ]);

        $result = $this->service->reActivateDomain('test.com');

        $this->assertTrue($result['success']);
        $this->assertEquals('test.com', $result['domain']);
        $this->assertEquals(15.00, $result['charged_amount']);
        $this->assertEquals('12345', $result['order_id']);
        $this->assertEquals('67890', $result['transaction_id']);
        $this->assertEquals('Domain reactivated successfully', $result['message']);

        // Verify the correct API call was made
        Http::assertSent(function (Request $request) {
            $url = $request->url();

            return str_contains($url, 'Command=namecheap.domains.reactivate') &&
                   str_contains($url, 'DomainName=test.com');
        });
    }

    public function test_handles_reactivation_failure(): void
    {
        $failureResponse = '<?xml version="1.0" encoding="UTF-8"?>
        <ApiResponse xmlns="http://api.namecheap.com/xml.response" Status="OK">
            <Errors />
            <Warnings />
            <RequestedCommand>namecheap.domains.reactivate</RequestedCommand>
            <CommandResponse Type="namecheap.domains.reactivate">
                <DomainReactivateResult Domain="test.com" IsSuccess="false" />
            </CommandResponse>
            <Server>SERVER-NAME</Server>
            <GMTTimeDifference>+0:00</GMTTimeDifference>
            <ExecutionTime>1.234</ExecutionTime>
        </ApiResponse>';

        Http::fake([
            '*' => Http::response($failureResponse, 200),
        ]);

        $result = $this->service->reActivateDomain('test.com');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Failed to reactivate domain', $result['message']);
    }

    public function test_handles_api_errors(): void
    {
        $errorResponse = '<?xml version="1.0" encoding="UTF-8"?>
        <ApiResponse xmlns="http://api.namecheap.com/xml.response" Status="ERROR">
            <Errors>
                <Error Number="2019166">Domain not found</Error>
            </Errors>
            <RequestedCommand>namecheap.domains.reactivate</RequestedCommand>
        </ApiResponse>';

        Http::fake([
            '*' => Http::response($errorResponse, 200),
        ]);

        $result = $this->service->reActivateDomain('test.com');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Domain not found', $result['message']);
    }

    public function test_handles_http_errors(): void
    {
        Http::fake([
            '*' => Http::response('', 500),
        ]);

        $result = $this->service->reActivateDomain('test.com');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Failed to reactivate domain', $result['message']);
    }

    public function test_handles_malformed_xml_response(): void
    {
        Http::fake([
            '*' => Http::response('invalid xml', 200),
        ]);

        $result = $this->service->reActivateDomain('test.com');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Failed to reactivate domain', $result['message']);
    }
}
