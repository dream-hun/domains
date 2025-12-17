<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Domain;

use App\Services\Domain\EppDomainService;
use Illuminate\Support\Facades\Config;
use ReflectionClass;
use Tests\TestCase;

final class DomainSuggestionTest extends TestCase
{
    private ?EppDomainService $service = null;

    private string $tempCertificatePath;

    protected function setUp(): void
    {
        parent::setUp();

        // Set mock EPP config values since these tests only test string manipulation methods
        // that don't require actual EPP connection
        $tempDir = storage_path('app/public');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        $this->tempCertificatePath = $tempDir.'/test_certificate.pem';

        // Create a dummy certificate file for testing
        if (! file_exists($this->tempCertificatePath)) {
            file_put_contents($this->tempCertificatePath, '-----BEGIN CERTIFICATE-----\nTEST\n-----END CERTIFICATE-----');
        }

        Config::set('services.epp', [
            'host' => 'test.epp.example.com',
            'username' => 'test_user',
            'password' => 'test_password',
            'port' => 700,
            'ssl' => true,
            'certificate' => $this->tempCertificatePath,
        ]);

        // Create instance without calling constructor using reflection
        $reflection = new ReflectionClass(EppDomainService::class);
        $this->service = $reflection->newInstanceWithoutConstructor();

        // Set the config property manually
        $configProperty = $reflection->getProperty('config');
        $configProperty->setAccessible(true);
        $configProperty->setValue($this->service, Config::get('services.epp'));
    }

    protected function tearDown(): void
    {
        // Clean up temp certificate file
        if (file_exists($this->tempCertificatePath)) {
            @unlink($this->tempCertificatePath);
        }

        parent::tearDown();
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
