<?php

declare(strict_types=1);

use AfriCC\EPP\Client as EPPClient;
use AfriCC\EPP\Frame\Response;
use App\Services\Domain\EppDomainService;

/**
 * Helper: create an EppDomainService with a pre-injected mock EPPClient
 * and the connected flag set to true, bypassing the real EPP connection.
 */
function eppServiceWithClient(EPPClient $client): EppDomainService
{
    config(['services.epp' => [
        'host' => 'epp.test.local',
        'port' => 700,
        'username' => 'testuser',
        'password' => 'testpass',
        'ssl' => true,
        'certificate' => __FILE__,
        'debug' => false,
    ]]);

    $service = new EppDomainService();

    $reflection = new ReflectionClass($service);

    $clientProp = $reflection->getProperty('client');
    $clientProp->setValue($service, $client);

    $connectedProp = $reflection->getProperty('connected');
    $connectedProp->setValue($service, true);

    return $service;
}

test('constructor does not throw when EPP config is empty', function (): void {
    config(['services.epp' => []]);

    $service = new EppDomainService();

    expect($service)->toBeInstanceOf(EppDomainService::class);
});

test('checkDomain throws when EPP host is not configured', function (): void {
    config(['services.epp' => []]);

    $service = new EppDomainService();

    expect(fn (): array => $service->checkDomain(['example.rw']))
        ->toThrow(Exception::class, 'EPP host is not configured');
});

test('checkDomain throws when EPP certificate is missing', function (): void {
    config(['services.epp' => [
        'host' => 'epp.test.local',
        'certificate' => '/nonexistent/path/cert.pem',
    ]]);

    $service = new EppDomainService();

    expect(fn (): array => $service->checkDomain(['example.rw']))
        ->toThrow(Exception::class, 'EPP certificate not found');
});

test('checkDomain returns available domain result', function (): void {
    $mockResponse = Mockery::mock(Response::class);
    $mockResponse->shouldReceive('data')->andReturn([
        'chkData' => [
            'cd' => [
                'name' => [
                    '_text' => 'example.rw',
                    '@attributes' => ['avail' => '1'],
                ],
                'reason' => null,
            ],
        ],
    ]);

    $mockClient = Mockery::mock(EPPClient::class);
    $mockClient->shouldReceive('request')->andReturn($mockResponse);
    $mockClient->shouldReceive('close');

    $service = eppServiceWithClient($mockClient);

    $results = $service->checkDomain(['example.rw']);

    expect($results)->toHaveKey('example.rw')
        ->and($results['example.rw']->available)->toBeTrue();
});

test('checkDomain returns unavailable domain result', function (): void {
    $mockResponse = Mockery::mock(Response::class);
    $mockResponse->shouldReceive('data')->andReturn([
        'chkData' => [
            'cd' => [
                'name' => [
                    '_text' => 'taken.rw',
                    '@attributes' => ['avail' => '0'],
                ],
                'reason' => ['_text' => 'Domain already registered'],
            ],
        ],
    ]);

    $mockClient = Mockery::mock(EPPClient::class);
    $mockClient->shouldReceive('request')->andReturn($mockResponse);
    $mockClient->shouldReceive('close');

    $service = eppServiceWithClient($mockClient);

    $results = $service->checkDomain(['taken.rw']);

    expect($results)->toHaveKey('taken.rw')
        ->and($results['taken.rw']->available)->toBeFalse()
        ->and($results['taken.rw']->reason)->toBe('Domain already registered');
});

test('checkAvailability formats results correctly for available domain', function (): void {
    $mockResponse = Mockery::mock(Response::class);
    $mockResponse->shouldReceive('data')->andReturn([
        'chkData' => [
            'cd' => [
                'name' => [
                    '_text' => 'free.rw',
                    '@attributes' => ['avail' => '1'],
                ],
                'reason' => null,
            ],
        ],
    ]);

    $mockClient = Mockery::mock(EPPClient::class);
    $mockClient->shouldReceive('request')->andReturn($mockResponse);
    $mockClient->shouldReceive('close');

    $service = eppServiceWithClient($mockClient);

    $results = $service->checkAvailability(['free.rw']);

    expect($results)->toHaveKey('free.rw')
        ->and($results['free.rw']['available'])->toBeTrue()
        ->and($results['free.rw'])->toHaveKey('reason');
});

test('checkAvailability returns error result when domain check fails', function (): void {
    $mockClient = Mockery::mock(EPPClient::class);
    $mockClient->shouldReceive('request')->andThrow(new Exception('EPP server error'));
    $mockClient->shouldReceive('close');

    $service = eppServiceWithClient($mockClient);

    $results = $service->checkAvailability(['error.rw']);

    expect($results)->toHaveKey('error.rw')
        ->and($results['error.rw']['available'])->toBeFalse()
        ->and($results['error.rw']['reason'])->toContain('Service temporarily unavailable');
});
