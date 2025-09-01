<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Domains;

use App\Actions\Domains\ToggleDomainLockAction;
use App\Models\Domain;
use App\Models\DomainPrice;
use App\Services\Domain\EppDomainService;
use App\Services\Domain\NamecheapDomainService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ToggleDomainLockActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_locks_local_domain_using_epp_service(): void
    {
        // Create a domain with local pricing
        $domain = Domain::factory()->create();
        DomainPrice::factory()->local()->create([
            'domain_id' => $domain->id,
        ]);

        // Mock EPP service
        $eppService = $this->mock(EppDomainService::class);
        $eppService->shouldReceive('setDomainLock')
            ->once()
            ->with($domain->name, true)
            ->andReturn(['success' => true]);

        // Mock Namecheap service (should not be used)
        $namecheapService = $this->mock(NamecheapDomainService::class);
        $namecheapService->shouldNotReceive('setDomainLock');

        // Execute action
        $action = new ToggleDomainLockAction($eppService, $namecheapService);
        $result = $action->execute($domain, true);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('message', $result);
    }

    public function test_locks_international_domain_using_namecheap_service(): void
    {
        // Create a domain with international pricing
        $domain = Domain::factory()->create();
        DomainPrice::factory()->international()->create([
            'domain_id' => $domain->id,
        ]);

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
        // Create a domain with local pricing
        $domain = Domain::factory()->create();
        DomainPrice::factory()->local()->create([
            'domain_id' => $domain->id,
        ]);

        // Mock EPP service to return an error
        $eppService = $this->mock(EppDomainService::class);
        $eppService->shouldReceive('setDomainLock')
            ->once()
            ->with($domain->name, true)
            ->andReturn([
                'success' => false,
                'message' => 'Service error',
            ]);

        // Mock Namecheap service (should not be used)
        $namecheapService = $this->mock(NamecheapDomainService::class);

        // Execute action
        $action = new ToggleDomainLockAction($eppService, $namecheapService);
        $result = $action->execute($domain, true);

        $this->assertFalse($result['success']);
        $this->assertEquals('Service error', $result['message']);
    }
}
