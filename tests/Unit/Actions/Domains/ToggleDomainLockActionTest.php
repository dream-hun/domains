<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Domains;

use App\Actions\Domains\ToggleDomainLockAction;
use App\Models\Domain;
use App\Services\Domain\EppDomainService;
use App\Services\Domain\NamecheapDomainService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ToggleDomainLockActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_domains_do_not_support_locking(): void
    {
        // Create a domain with local pricing
        $domain = Domain::factory()->rwDomain()->create();

        // Mock services should not receive lock calls
        $eppService = $this->mock(EppDomainService::class);
        $eppService->shouldNotReceive('setDomainLock');

        $namecheapService = $this->mock(NamecheapDomainService::class);
        $namecheapService->shouldNotReceive('setDomainLock');

        // Execute action
        $action = new ToggleDomainLockAction($eppService, $namecheapService);
        $result = $action->execute($domain, true);

        $this->assertFalse($result['success']);
        $this->assertEquals('Domain locking is not supported for local domains.', $result['message']);
    }

    public function test_locks_international_domain_using_namecheap_service(): void
    {
        // Create a domain with international pricing
        $domain = Domain::factory()->comDomain()->create();

        // Mock EPP service (should not be used)
        $eppService = $this->mock(EppDomainService::class);
        $eppService->shouldNotReceive('setDomainLock');

        // Mock Namecheap service
        $namecheapService = $this->mock(NamecheapDomainService::class);
        $namecheapService->shouldReceive('setDomainLock')
            ->once()
            ->with($domain->name, true)
            ->andReturn(['success' => true]);

        // Execute action
        $action = new ToggleDomainLockAction($eppService, $namecheapService);
        $result = $action->execute($domain, true);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('message', $result);
    }

    public function test_handles_service_errors_gracefully(): void
    {
        // Create a domain with international pricing
        $domain = Domain::factory()->comDomain()->create();

        // Mock Namecheap service to return an error
        $eppService = $this->mock(EppDomainService::class);
        $eppService->shouldNotReceive('setDomainLock');

        $namecheapService = $this->mock(NamecheapDomainService::class);
        $namecheapService->shouldReceive('setDomainLock')
            ->once()
            ->with($domain->name, true)
            ->andReturn([
                'success' => false,
                'message' => 'Service error',
            ]);

        // Execute action
        $action = new ToggleDomainLockAction($eppService, $namecheapService);
        $result = $action->execute($domain, true);

        $this->assertFalse($result['success']);
        $this->assertEquals('Service error', $result['message']);
    }
}
