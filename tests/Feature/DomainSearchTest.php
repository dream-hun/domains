<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\DomainType;
use App\Livewire\DomainSearch;
use App\Models\DomainPrice;
use App\Services\Domain\EppDomainService;
use App\Services\Domain\InternationalDomainService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

final class DomainSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_domain_search_uses_international_service_for_international_domains(): void
    {
        // Create a mock for EppDomainService
        $eppService = Mockery::mock(EppDomainService::class);

        // Create a mock for InternationalDomainService
        $internationalService = Mockery::mock(InternationalDomainService::class);

        // Set up expectations for the international service
        $internationalService->shouldReceive('checkAvailability')
            ->once()
            ->with(['example.com'])
            ->andReturn([
                'available' => true,
                'reason' => 'Domain is available',
            ]);

        // Create a test domain price with international type
        DomainPrice::create([
            'tld' => '.com',
            'register_price' => 1000,
            'renewal_price' => 1000,
            'transfer_price' => 800,
            'redemption_price' => 1500,
            'status' => 'active',
            'type' => DomainType::International,
        ]);

        // Test the Livewire component
        Livewire::test(DomainSearch::class, ['eppService' => $eppService, 'internationalService' => $internationalService])
            ->set('domain', 'example')
            ->set('extension', '.com')
            ->call('search')
            ->assertSet('isSearching', false)
            ->assertSet('error', '');

        // The test passes if the international service's checkAvailability method was called once
    }

    public function test_domain_search_uses_epp_service_for_local_domains(): void
    {
        // Create a mock for EppDomainService
        $eppService = Mockery::mock(EppDomainService::class);

        // Set up expectations for the EPP service
        $eppService->shouldReceive('checkDomain')
            ->once()
            ->with(['example.rw'])
            ->andReturn([
                'example.rw' => (object) [
                    'available' => true,
                    'reason' => 'Domain is available',
                ],
            ]);

        // Create a mock for InternationalDomainService
        $internationalService = Mockery::mock(InternationalDomainService::class);

        // Create a test domain price with local type
        DomainPrice::create([
            'tld' => '.rw',
            'register_price' => 1000,
            'renewal_price' => 1000,
            'transfer_price' => 800,
            'redemption_price' => 1500,
            'status' => 'active',
            'type' => DomainType::Local,
        ]);

        // Test the Livewire component
        Livewire::test(DomainSearch::class, ['eppService' => $eppService, 'internationalService' => $internationalService])
            ->set('domain', 'example')
            ->set('extension', '.rw')
            ->call('search')
            ->assertSet('isSearching', false)
            ->assertSet('error', '');

        // The test passes if the EPP service's checkDomain method was called once
    }
}
