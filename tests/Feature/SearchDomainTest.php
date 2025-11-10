<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\DomainType;
use App\Models\DomainPrice;
use App\Services\Domain\DomainServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

final class SearchDomainTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test that the search endpoint returns the correct response structure
     */
    public function test_search_endpoint_returns_correct_structure(): void
    {
        // Create a test domain price
        DomainPrice::query()->create([
            'uuid' => '123e4567-e89b-12d3-a456-426614174000',
            'tld' => '.com',
            'type' => DomainType::International,
            'register_price' => 1000,
            'renewal_price' => 1000,
            'transfer_price' => 1000,
            'status' => 'active',
        ]);

        // Mock the domain service to always return available
        $this->mock(DomainServiceInterface::class, function ($mock): void {
            $mock->shouldReceive('checkAvailability')
                ->andReturn(['available' => true, 'reason' => 'Domain is available']);
        });

        // Send a request to the search endpoint
        $response = $this->postJson('/api/domains/search', [
            'query' => 'example',
            'type' => 'international',
        ]);

        // Assert the response structure
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'query',
                'type',
                'results' => [
                    '*' => [
                        'domain',
                        'available',
                        'type',
                        'register_price',
                        'renewal_price',
                        'transfer_price',
                    ],
                ],
            ]);
    }

    /**
     * Test that the search endpoint filters by domain type
     */
    public function test_search_endpoint_filters_by_type(): void
    {
        // Create test domain prices
        DomainPrice::query()->create([
            'uuid' => '123e4567-e89b-12d3-a456-426614174000',
            'tld' => '.com',
            'type' => DomainType::International,
            'register_price' => 1000,
            'renewal_price' => 1000,
            'transfer_price' => 1000,
            'status' => 'active',
        ]);

        DomainPrice::query()->create([
            'uuid' => '223e4567-e89b-12d3-a456-426614174000',
            'tld' => '.co.ke',
            'type' => DomainType::Local,
            'register_price' => 500,
            'renewal_price' => 500,
            'transfer_price' => 500,
            'status' => 'active',
        ]);

        // Mock the domain service to always return available
        $this->mock(DomainServiceInterface::class, function ($mock): void {
            $mock->shouldReceive('checkAvailability')
                ->andReturn(['available' => true, 'reason' => 'Domain is available']);
        });

        // Test filtering by international
        $response = $this->postJson('/api/domains/search', [
            'query' => 'example',
            'type' => 'international',
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'results')
            ->assertJson([
                'results' => [
                    [
                        'domain' => 'example.com',
                        'type' => 'international',
                    ],
                ],
            ]);

        // Test filtering by local
        $response = $this->postJson('/api/domains/search', [
            'query' => 'example',
            'type' => 'local',
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'results')
            ->assertJson([
                'results' => [
                    [
                        'domain' => 'example.co.ke',
                        'type' => 'local',
                    ],
                ],
            ]);

        // Test with all types
        $response = $this->postJson('/api/domains/search', [
            'query' => 'example',
            'type' => 'all',
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(2, 'results');
    }

    /**
     * Test validation errors
     */
    public function test_search_endpoint_validates_input(): void
    {
        // Test missing query
        $response = $this->postJson('/api/domains/search', [
            'type' => 'all',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['query']);

        // Test invalid type
        $response = $this->postJson('/api/domains/search', [
            'query' => 'example',
            'type' => 'invalid',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }
}
