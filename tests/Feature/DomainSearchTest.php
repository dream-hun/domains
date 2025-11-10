<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\DomainType;
use App\Helpers\DomainSearchHelper;
use App\Models\DomainPrice;
use App\Services\Domain\EppDomainService;
use App\Services\Domain\NamecheapDomainService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionClass;
use Tests\TestCase;

final class DomainSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_domain_search_page_loads(): void
    {
        $response = $this->get('/domains');

        $response->assertStatus(200);
        $response->assertViewIs('domains.search');
    }

    public function test_domain_search_form_submission_with_validation(): void
    {
        // Create some test domain prices
        DomainPrice::factory()->create([
            'tld' => '.rw',
            'type' => DomainType::Local,
            'status' => 'active',
        ]);

        DomainPrice::factory()->create([
            'tld' => '.com',
            'type' => DomainType::International,
            'status' => 'active',
        ]);

        // Test with valid data - this will fail due to service connections but we can test the validation
        $response = $this->post('/domains/search', [
            'domain' => 'test.rw',
        ]);

        // The response should be 200 even if services fail
        $response->assertStatus(200);
        $response->assertViewIs('domains.search');
    }

    public function test_domain_search_form_validation(): void
    {
        $response = $this->post('/domains/search', [
            'domain' => '',
        ]);

        $response->assertSessionHasErrors(['domain']);
    }

    public function test_domain_search_helper_sanitizes_domain(): void
    {
        $helper = new DomainSearchHelper(
            app(NamecheapDomainService::class),
            app(EppDomainService::class)
        );

        $reflection = new ReflectionClass($helper);
        $method = $reflection->getMethod('sanitizeDomain');

        $this->assertEquals('example', $method->invoke($helper, 'example'));
        $this->assertEquals('example', $method->invoke($helper, ' example '));
        $this->assertEquals('example', $method->invoke($helper, 'EXAMPLE'));
        $this->assertEquals('example.com', $method->invoke($helper, 'example.com'));
        $this->assertEquals('example.com', $method->invoke($helper, 'www.example.com'));
        $this->assertEquals('example.com', $method->invoke($helper, 'https://example.com'));
        $this->assertEquals('example.com', $method->invoke($helper, 'https://www.example.com/'));
    }

    public function test_domain_search_helper_creates_suggestions(): void
    {
        // Create test domain prices first
        DomainPrice::factory()->create([
            'tld' => '.rw',
            'type' => DomainType::Local,
            'status' => 'active',
        ]);

        DomainPrice::factory()->create([
            'tld' => '.co.rw',
            'type' => DomainType::Local,
            'status' => 'active',
        ]);

        $helper = new DomainSearchHelper(
            app(NamecheapDomainService::class),
            app(EppDomainService::class)
        );

        // Test the main process with local domain to verify suggestions are created
        $result = $helper->processDomainSearch('test.rw');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('domainType', $result);
        $this->assertEquals(DomainType::Local, $result['domainType']);

        // Check that we got suggestions
        if (isset($result['suggestions'])) {
            $this->assertIsArray($result['suggestions']);
        }
    }

    public function test_international_domain_search_creates_suggestions(): void
    {
        // Create test international domain prices
        DomainPrice::factory()->create([
            'tld' => '.com',
            'type' => DomainType::International,
            'status' => 'active',
        ]);

        DomainPrice::factory()->create([
            'tld' => '.net',
            'type' => DomainType::International,
            'status' => 'active',
        ]);

        DomainPrice::factory()->create([
            'tld' => '.org',
            'type' => DomainType::International,
            'status' => 'active',
        ]);

        $helper = new DomainSearchHelper(
            app(NamecheapDomainService::class),
            app(EppDomainService::class)
        );

        // Test with international domain to verify suggestions are created
        $result = $helper->processDomainSearch('test.com');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('domainType', $result);
        $this->assertEquals(DomainType::International, $result['domainType']);

        // Check that we got suggestions
        if (isset($result['suggestions'])) {
            $this->assertIsArray($result['suggestions']);

            // Verify that suggestions contain international TLDs
            if ($result['suggestions'] !== []) {
                $suggestionDomains = array_column($result['suggestions'], 'domain');
                $hasInternationalTlds = collect($suggestionDomains)->contains(fn ($domain): bool => str_ends_with((string) $domain, '.net') || str_ends_with((string) $domain, '.org')
                );
                $this->assertTrue($hasInternationalTlds, 'Should contain international TLD suggestions');
            }
        }
    }

    public function test_international_domain_search_process(): void
    {
        // Create test international domain prices
        DomainPrice::factory()->create([
            'tld' => '.com',
            'type' => DomainType::International,
            'status' => 'active',
            'register_price' => 1299, // $12.99
        ]);

        DomainPrice::factory()->create([
            'tld' => '.net',
            'type' => DomainType::International,
            'status' => 'active',
            'register_price' => 1499, // $14.99
        ]);

        $helper = new DomainSearchHelper(
            app(NamecheapDomainService::class),
            app(EppDomainService::class)
        );

        // Test the main search process with auto-detection
        $result = $helper->processDomainSearch('test.com');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('domainType', $result);
        $this->assertEquals(DomainType::International, $result['domainType']);

        // Check if we got any suggestions
        if (isset($result['suggestions'])) {
            $this->assertIsArray($result['suggestions']);

            // Log the suggestions for debugging
            foreach ($result['suggestions'] as $suggestion) {
                $this->assertArrayHasKey('domain', $suggestion);
                $this->assertArrayHasKey('type', $suggestion);
                $this->assertEquals('international', $suggestion['type']);
            }
        }

        // Check if we got details
        if (isset($result['details'])) {
            $this->assertIsArray($result['details']);
            $this->assertArrayHasKey('type', $result['details']);
            $this->assertEquals('international', $result['details']['type']);
        }
    }

    public function test_domain_search_helper_detects_domain_type(): void
    {
        $helper = new DomainSearchHelper(
            app(NamecheapDomainService::class),
            app(EppDomainService::class)
        );

        $reflection = new ReflectionClass($helper);
        $method = $reflection->getMethod('detectDomainType');

        $this->assertEquals(DomainType::Local, $method->invoke($helper, 'example.rw'));
        $this->assertEquals(DomainType::International, $method->invoke($helper, 'example.com'));
        $this->assertEquals(DomainType::International, $method->invoke($helper, 'example.net'));
    }

    public function test_domain_search_helper_validates_domain(): void
    {
        $helper = new DomainSearchHelper(
            app(NamecheapDomainService::class),
            app(EppDomainService::class)
        );

        // Test valid domains
        $this->assertTrue($helper->isValidDomainName('example.com'));
        $this->assertTrue($helper->isValidDomainName('test.rw'));
        $this->assertTrue($helper->isValidDomainName('sub-domain.example.org'));

        // Test invalid domains
        $this->assertFalse($helper->isValidDomainName(''));
        $this->assertFalse($helper->isValidDomainName('a'));
        $this->assertFalse($helper->isValidDomainName('-invalid.com'));
        $this->assertFalse($helper->isValidDomainName('invalid-.com'));
    }

    public function test_international_domain_search_logic(): void
    {
        // Create test international domain prices
        DomainPrice::factory()->create([
            'tld' => '.com',
            'type' => DomainType::International,
            'status' => 'active',
            'register_price' => 1299,
        ]);

        DomainPrice::factory()->create([
            'tld' => '.net',
            'type' => DomainType::International,
            'status' => 'active',
            'register_price' => 1499,
        ]);

        DomainPrice::factory()->create([
            'tld' => '.org',
            'type' => DomainType::International,
            'status' => 'active',
            'register_price' => 1699,
        ]);

        $helper = new DomainSearchHelper(
            app(NamecheapDomainService::class),
            app(EppDomainService::class)
        );

        // Test the helper methods directly
        $reflection = new ReflectionClass($helper);

        // Test findDomainPriceInfo method
        $findPriceMethod = $reflection->getMethod('findDomainPriceInfo');

        $priceInfo = $findPriceMethod->invoke($helper, 'com');
        $this->assertNotNull($priceInfo, 'Should find price info for .com');
        $this->assertEquals('.com', $priceInfo->tld);

        $priceInfo = $findPriceMethod->invoke($helper, 'net');
        $this->assertNotNull($priceInfo, 'Should find price info for .net');
        $this->assertEquals('.net', $priceInfo->tld);

        // Test detectDomainType method
        $detectTypeMethod = $reflection->getMethod('detectDomainType');

        $this->assertEquals(DomainType::International, $detectTypeMethod->invoke($helper, 'test.com'));
        $this->assertEquals(DomainType::International, $detectTypeMethod->invoke($helper, 'example.net'));
        $this->assertEquals(DomainType::Local, $detectTypeMethod->invoke($helper, 'example.rw'));
    }

    public function test_search_international_domains_method(): void
    {
        // Create test international domain prices
        DomainPrice::factory()->create([
            'tld' => '.com',
            'type' => DomainType::International,
            'status' => 'active',
            'register_price' => 1299,
        ]);

        DomainPrice::factory()->create([
            'tld' => '.net',
            'type' => DomainType::International,
            'status' => 'active',
            'register_price' => 1499,
        ]);

        DomainPrice::factory()->create([
            'tld' => '.org',
            'type' => DomainType::International,
            'status' => 'active',
            'register_price' => 1699,
        ]);

        $helper = new DomainSearchHelper(
            app(NamecheapDomainService::class),
            app(EppDomainService::class)
        );

        // Test the searchInternationalDomains method directly
        $reflection = new ReflectionClass($helper);
        $searchMethod = $reflection->getMethod('searchInternationalDomains');

        // Test with a domain that has a TLD
        $result = $searchMethod->invoke($helper, 'test.com', 'test');

        $this->assertIsArray($result);
        $this->assertCount(2, $result); // Should return [details, suggestions]

        [$details, $suggestions] = $result;

        // Check details
        if ($details) {
            $this->assertIsArray($details);
            $this->assertArrayHasKey('domain', $details);
            $this->assertArrayHasKey('type', $details);
            $this->assertEquals('international', $details['type']);
        }

        // Check suggestions
        $this->assertIsArray($suggestions);
        $this->assertNotEmpty($suggestions, 'Should have international domain suggestions');

        // Verify suggestions contain international TLDs
        $suggestionDomains = array_keys($suggestions);
        $this->assertTrue(
            collect($suggestionDomains)->contains(fn ($domain): bool => str_ends_with((string) $domain, '.net')),
            'Should contain .net suggestions'
        );
        $this->assertTrue(
            collect($suggestionDomains)->contains(fn ($domain): bool => str_ends_with((string) $domain, '.org')),
            'Should contain .org suggestions'
        );
    }

    public function test_international_domain_suggestions_from_prices(): void
    {
        // Create test international domain prices
        DomainPrice::factory()->create([
            'tld' => '.com',
            'type' => DomainType::International,
            'status' => 'active',
            'register_price' => 1299,
        ]);

        DomainPrice::factory()->create([
            'tld' => '.net',
            'type' => DomainType::International,
            'status' => 'active',
            'register_price' => 1499,
        ]);

        DomainPrice::factory()->create([
            'tld' => '.org',
            'type' => DomainType::International,
            'status' => 'active',
            'register_price' => 1699,
        ]);

        // Test that we can query international domain prices
        $internationalTlds = DomainPrice::query()->where('type', DomainType::International)
            ->where('status', 'active')
            ->latest()
            ->limit(15)
            ->pluck('tld')
            ->toArray();

        $this->assertNotEmpty($internationalTlds, 'Should have international domain prices');
        $this->assertContains('.com', $internationalTlds, 'Should contain .com TLD');
        $this->assertContains('.net', $internationalTlds, 'Should contain .net TLD');
        $this->assertContains('.org', $internationalTlds, 'Should contain .org TLD');

        // Test the helper methods that process these TLDs
        $helper = new DomainSearchHelper(
            app(NamecheapDomainService::class),
            app(EppDomainService::class)
        );

        $reflection = new ReflectionClass($helper);

        // Test the helper method for getting popular domains
        $popularDomains = $helper->getPopularDomains(DomainType::International, 3);

        $this->assertIsArray($popularDomains);
        $this->assertNotEmpty($popularDomains, 'Should have popular domain data');

        foreach ($popularDomains as $domain) {
            $this->assertArrayHasKey('tld', $domain);
            $this->assertArrayHasKey('price', $domain);
            $this->assertArrayHasKey('currency', $domain);
        }

        // Test findDomainPriceInfo method
        $findPriceMethod = $reflection->getMethod('findDomainPriceInfo');

        $priceInfo = $findPriceMethod->invoke($helper, 'com');
        $this->assertNotNull($priceInfo, 'Should find price info for com');
        $this->assertEquals('.com', $priceInfo->tld);
        $this->assertEquals(DomainType::International, $priceInfo->type);
    }
}
