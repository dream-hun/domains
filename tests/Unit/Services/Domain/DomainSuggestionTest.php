<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Domain;

use App\Services\Domain\EppDomainService;
use ReflectionClass;
use Tests\TestCase;

final class DomainSuggestionTest extends TestCase
{
    private ?EppDomainService $service = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Skip tests if EPP is not configured (e.g., in CI environment)
        if (empty(config('services.epp.host'))) {
            $this->markTestSkipped('EPP is not configured. These tests require a real EPP connection.');
        }

        $this->service = new EppDomainService();
    }

    public function test_domain_suggestion_generates_suggestions(): void
    {
        // Use reflection to test private methods
        $reflection = new ReflectionClass($this->service);

        // Test extractBaseName method
        $extractBaseNameMethod = $reflection->getMethod('extractBaseName');

        $baseName = $extractBaseNameMethod->invoke($this->service, 'example.com');
        $this->assertEquals('example', $baseName);

        // Test extractTld method
        $extractTldMethod = $reflection->getMethod('extractTld');

        $tld = $extractTldMethod->invoke($this->service, 'example.com');
        $this->assertEquals('com', $tld);

        // Test generateDomainSuggestions method
        $generateSuggestionsMethod = $reflection->getMethod('generateDomainSuggestions');

        $suggestions = $generateSuggestionsMethod->invoke($this->service, 'example');
        $this->assertIsArray($suggestions);
        $this->assertNotEmpty($suggestions);

        // Test getSuggestionType method
        $getSuggestionTypeMethod = $reflection->getMethod('getSuggestionType');

        $suggestionType = $getSuggestionTypeMethod->invoke($this->service, 'examplehub', 'example');
        $this->assertIsString($suggestionType);
    }

    public function test_domain_suggestion_handles_complex_domains(): void
    {
        $reflection = new ReflectionClass($this->service);

        $extractBaseNameMethod = $reflection->getMethod('extractBaseName');

        // Test with subdomain
        $baseName = $extractBaseNameMethod->invoke($this->service, 'sub.example.co.uk');
        $this->assertEquals('sub.example.co', $baseName);

        // Test with single level domain
        $baseName = $extractBaseNameMethod->invoke($this->service, 'example');
        $this->assertEquals('', $baseName);
    }

    public function test_domain_suggestion_handles_edge_cases(): void
    {
        $reflection = new ReflectionClass($this->service);

        $extractTldMethod = $reflection->getMethod('extractTld');

        // Test with multi-level TLD
        $tld = $extractTldMethod->invoke($this->service, 'example.co.uk');
        $this->assertEquals('uk', $tld);

        // Test with single level domain
        $tld = $extractTldMethod->invoke($this->service, 'example');
        $this->assertEquals('example', $tld);
    }
}
