<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Domain;

use App\Services\Domain\EppDomainService;
use ReflectionClass;
use Tests\TestCase;

final class DomainSuggestionTest extends TestCase
{
    public function test_domain_suggestion_generates_suggestions(): void
    {
        // Since EppDomainService is final, we'll test the suggestion logic directly
        // by creating a test instance and testing the private methods via reflection

        $service = new EppDomainService();

        // Use reflection to test private methods
        $reflection = new ReflectionClass($service);

        // Test extractBaseName method
        $extractBaseNameMethod = $reflection->getMethod('extractBaseName');
        $extractBaseNameMethod->setAccessible(true);

        $baseName = $extractBaseNameMethod->invoke($service, 'example.com');
        $this->assertEquals('example', $baseName);

        // Test extractTld method
        $extractTldMethod = $reflection->getMethod('extractTld');
        $extractTldMethod->setAccessible(true);

        $tld = $extractTldMethod->invoke($service, 'example.com');
        $this->assertEquals('com', $tld);

        // Test generateDomainSuggestions method
        $generateSuggestionsMethod = $reflection->getMethod('generateDomainSuggestions');
        $generateSuggestionsMethod->setAccessible(true);

        $suggestions = $generateSuggestionsMethod->invoke($service, 'example');
        $this->assertIsArray($suggestions);
        $this->assertNotEmpty($suggestions);

        // Test getSuggestionType method
        $getSuggestionTypeMethod = $reflection->getMethod('getSuggestionType');
        $getSuggestionTypeMethod->setAccessible(true);

        $suggestionType = $getSuggestionTypeMethod->invoke($service, 'examplehub', 'example');
        $this->assertIsString($suggestionType);
    }

    public function test_domain_suggestion_handles_complex_domains(): void
    {
        $service = new EppDomainService();
        $reflection = new ReflectionClass($service);

        $extractBaseNameMethod = $reflection->getMethod('extractBaseName');
        $extractBaseNameMethod->setAccessible(true);

        // Test with subdomain
        $baseName = $extractBaseNameMethod->invoke($service, 'sub.example.co.uk');
        $this->assertEquals('sub.example.co', $baseName);

        // Test with single level domain
        $baseName = $extractBaseNameMethod->invoke($service, 'example');
        $this->assertEquals('', $baseName);
    }

    public function test_domain_suggestion_handles_edge_cases(): void
    {
        $service = new EppDomainService();
        $reflection = new ReflectionClass($service);

        $extractTldMethod = $reflection->getMethod('extractTld');
        $extractTldMethod->setAccessible(true);

        // Test with multi-level TLD
        $tld = $extractTldMethod->invoke($service, 'example.co.uk');
        $this->assertEquals('uk', $tld);

        // Test with single level domain
        $tld = $extractTldMethod->invoke($service, 'example');
        $this->assertEquals('example', $tld);
    }
}
