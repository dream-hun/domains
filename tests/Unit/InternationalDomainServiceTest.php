<?php

declare(strict_types=1);

use App\Models\Contact;
use App\Services\Domain\InternationalDomainService;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    $this->service = new InternationalDomainService();

    // Mock config values
    config([
        'services.namecheap.apiUser' => 'test_user',
        'services.namecheap.apiKey' => 'test_key',
        'services.namecheap.username' => 'test_username',
        'services.namecheap.client' => '127.0.0.1',
        'services.namecheap.apiBaseUrl' => 'https://api.sandbox.namecheap.com/xml.response',
    ]);
});

it('can check domain availability', function (): void {
    $xmlResponse = '<?xml version="1.0" encoding="utf-8"?>
    <ApiResponse Status="OK" xmlns="http://api.namecheap.com/xml.response">
        <Errors/>
        <Warnings/>
        <RequestedCommand>namecheap.domains.check</RequestedCommand>
        <CommandResponse Type="namecheap.domains.check">
            <DomainCheckResult Domain="example.com" Available="true"/>
        </CommandResponse>
        <Server>WEB1-SANDBOX1</Server>
        <GMTTimeDifference>--5:00</GMTTimeDifference>
        <ExecutionTime>0.008</ExecutionTime>
    </ApiResponse>';

    Http::fake([
        '*' => Http::response($xmlResponse, 200),
    ]);

    $result = $this->service->checkAvailability(['example.com']);

    expect($result)->toBeArray()
        ->and($result['available'])->toBeTrue()
        ->and($result['reason'])->toBe('');
});

it('can get domain information', function (): void {
    $xmlResponse = '<?xml version="1.0" encoding="utf-8"?>
    <ApiResponse Status="OK" xmlns="http://api.namecheap.com/xml.response">
        <Errors/>
        <Warnings/>
        <RequestedCommand>namecheap.domains.getInfo</RequestedCommand>
        <CommandResponse Type="namecheap.domains.getInfo">
            <DomainGetInfoResult Name="example.com" Status="Ok" 
                                Registrant="test@example.com" 
                                CreatedDate="01/01/2023"
                                ExpiredDate="01/01/2024"/>
        </CommandResponse>
        <Server>WEB1-SANDBOX1</Server>
        <GMTTimeDifference>--5:00</GMTTimeDifference>
        <ExecutionTime>0.008</ExecutionTime>
    </ApiResponse>';

    Http::fake([
        '*' => Http::response($xmlResponse, 200),
    ]);

    $result = $this->service->getDomainInfo('example.com');

    expect($result)->toBeArray()
        ->and($result['success'])->toBeTrue()
        ->and($result['domain'])->toBe('example.com')
        ->and($result['status'])->toBe(['Ok'])
        ->and($result['registrant'])->toBe('test@example.com');
});

it('can suggest domains', function (): void {
    $xmlResponse = '<?xml version="1.0" encoding="utf-8"?>
    <ApiResponse Status="OK" xmlns="http://api.namecheap.com/xml.response">
        <Errors/>
        <Warnings/>
        <RequestedCommand>namecheap.domains.check</RequestedCommand>
        <CommandResponse Type="namecheap.domains.check">
            <DomainCheckResult Domain="example.com" Available="true"/>
            <DomainCheckResult Domain="example.net" Available="false"/>
            <DomainCheckResult Domain="example.org" Available="true"/>
        </CommandResponse>
        <Server>WEB1-SANDBOX1</Server>
        <GMTTimeDifference>--5:00</GMTTimeDifference>
        <ExecutionTime>0.008</ExecutionTime>
    </ApiResponse>';

    Http::fake([
        '*' => Http::response($xmlResponse, 200),
    ]);

    $result = $this->service->suggestDomains('example');

    expect($result)->toBeArray()
        ->and($result['example.com']['available'])->toBeTrue()
        ->and($result['example.net']['available'])->toBeFalse()
        ->and($result['example.org']['available'])->toBeTrue();
});

it('can create a contact', function (): void {
    $xmlResponse = '<?xml version="1.0" encoding="utf-8"?>
    <ApiResponse Status="OK" xmlns="http://api.namecheap.com/xml.response">
        <Errors/>
        <Warnings/>
        <RequestedCommand>namecheap.domains.contacts.create</RequestedCommand>
        <CommandResponse Type="namecheap.domains.contacts.create">
            <ContactCreateResult ContactID="12345"/>
        </CommandResponse>
        <Server>WEB1-SANDBOX1</Server>
        <GMTTimeDifference>--5:00</GMTTimeDifference>
        <ExecutionTime>0.008</ExecutionTime>
    </ApiResponse>';

    Http::fake([
        '*' => Http::response($xmlResponse, 200),
    ]);

    $contactData = [
        'domain' => 'example.com',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@example.com',
        'phone' => '+1.1234567890',
        'address_one' => '123 Main St',
        'city' => 'Anytown',
        'state_province' => 'CA',
        'postal_code' => '12345',
        'country_code' => 'US',
        'user_id' => 1,
    ];

    $contact = $this->service->createContact($contactData);

    expect($contact)->toBeInstanceOf(Contact::class)
        ->and($contact->contact_id)->toBe('12345')
        ->and($contact->provider)->toBe('namecheap')
        ->and($contact->first_name)->toBe('John')
        ->and($contact->last_name)->toBe('Doe')
        ->and($contact->email)->toBe('john@example.com');
});

it('can register a domain', function (): void {
    // Mock contact creation response first
    $contactXmlResponse = '<?xml version="1.0" encoding="utf-8"?>
    <ApiResponse Status="OK" xmlns="http://api.namecheap.com/xml.response">
        <Errors/>
        <Warnings/>
        <RequestedCommand>namecheap.domains.contacts.create</RequestedCommand>
        <CommandResponse Type="namecheap.domains.contacts.create">
            <ContactCreateResult ContactID="12345"/>
        </CommandResponse>
        <Server>WEB1-SANDBOX1</Server>
        <GMTTimeDifference>--5:00</GMTTimeDifference>
        <ExecutionTime>0.008</ExecutionTime>
    </ApiResponse>';

    $domainXmlResponse = '<?xml version="1.0" encoding="utf-8"?>
    <ApiResponse Status="OK" xmlns="http://api.namecheap.com/xml.response">
        <Errors/>
        <Warnings/>
        <RequestedCommand>namecheap.domains.create</RequestedCommand>
        <CommandResponse Type="namecheap.domains.create">
            <DomainCreateResult OrderId="67890"/>
        </CommandResponse>
        <Server>WEB1-SANDBOX1</Server>
        <GMTTimeDifference>--5:00</GMTTimeDifference>
        <ExecutionTime>0.008</ExecutionTime>
    </ApiResponse>';

    Http::fake([
        '*contacts.create*' => Http::response($contactXmlResponse, 200),
        '*domains.create*' => Http::response($domainXmlResponse, 200),
    ]);

    $contactInfo = [
        'domain' => 'example.com',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@example.com',
        'phone' => '+1.1234567890',
        'address_one' => '123 Main St',
        'city' => 'Anytown',
        'state_province' => 'CA',
        'postal_code' => '12345',
        'country_code' => 'US',
        'user_id' => 1,
    ];

    $result = $this->service->registerDomain('example.com', $contactInfo, 1);

    expect($result)->toBeArray()
        ->and($result['success'])->toBeTrue()
        ->and($result['domain'])->toBe('example.com')
        ->and($result['message'])->toContain('67890');
});

it('handles API errors gracefully', function (): void {
    $xmlResponse = '<?xml version="1.0" encoding="utf-8"?>
    <ApiResponse Status="ERROR" xmlns="http://api.namecheap.com/xml.response">
        <Errors>
            <Error Number="2019166">The domain name is invalid</Error>
        </Errors>
        <Warnings/>
        <RequestedCommand>namecheap.domains.check</RequestedCommand>
        <CommandResponse Type="namecheap.domains.check"/>
        <Server>WEB1-SANDBOX1</Server>
        <GMTTimeDifference>--5:00</GMTTimeDifference>
        <ExecutionTime>0.008</ExecutionTime>
    </ApiResponse>';

    Http::fake([
        '*' => Http::response($xmlResponse, 200),
    ]);

    expect(fn () => $this->service->checkAvailability(['invalid-domain']))
        ->toThrow(Exception::class, 'Namecheap API error: The domain name is invalid');
});

it('can extract first and last names correctly', function (): void {
    $reflection = new ReflectionClass($this->service);

    $extractFirstName = $reflection->getMethod('extractFirstName');
    $extractFirstName->setAccessible(true);

    $extractLastName = $reflection->getMethod('extractLastName');
    $extractLastName->setAccessible(true);

    expect($extractFirstName->invoke($this->service, 'John Doe Smith'))->toBe('John')
        ->and($extractLastName->invoke($this->service, 'John Doe Smith'))->toBe('Doe Smith')
        ->and($extractFirstName->invoke($this->service, 'John'))->toBe('John')
        ->and($extractLastName->invoke($this->service, 'John'))->toBe('');
});
