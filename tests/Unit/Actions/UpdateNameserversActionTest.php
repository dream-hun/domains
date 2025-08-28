<?php

declare(strict_types=1);

use App\Models\Domain;
use App\Models\Nameserver;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->domain = Domain::factory()->create(['owner_id' => $this->user->id]);
});

test('nameserver model creates correctly', function (): void {
    $nameserver = Nameserver::factory()->create([
        'name' => 'ns1.example.com',
        'type' => 'default',
        'status' => 'active',
    ]);

    expect($nameserver->name)->toBe('ns1.example.com');
    expect($nameserver->type)->toBe('default');
    expect($nameserver->status)->toBe('active');
    expect($nameserver->uuid)->not->toBeNull();
});

test('domain can have multiple nameservers', function (): void {
    $nameserver1 = Nameserver::factory()->create(['name' => 'ns1.example.com']);
    $nameserver2 = Nameserver::factory()->create(['name' => 'ns2.example.com']);

    $this->domain->nameservers()->attach([$nameserver1->id, $nameserver2->id]);
    $this->domain->refresh();

    expect($this->domain->nameservers)->toHaveCount(2);
    expect($this->domain->nameservers->pluck('name')->toArray())
        ->toContain('ns1.example.com', 'ns2.example.com');
});

test('nameserver can be reused across domains', function (): void {
    $nameserver = Nameserver::factory()->create(['name' => 'ns1.cloudflare.com']);

    // Create domain with specific domain price to avoid conflicts
    $domainPrice = App\Models\DomainPrice::factory()->create(['tld' => '.test']);
    $domain2 = Domain::factory()->create([
        'owner_id' => $this->user->id,
        'domain_price_id' => $domainPrice->id,
        'name' => 'example2.test',
    ]);

    $this->domain->nameservers()->attach($nameserver->id);
    $domain2->nameservers()->attach($nameserver->id);

    expect($this->domain->nameservers)->toHaveCount(1);
    expect($domain2->nameservers)->toHaveCount(1);
    expect($this->domain->nameservers->first()->id)->toBe($nameserver->id);
    expect($domain2->nameservers->first()->id)->toBe($nameserver->id);
});

test('nameserver firstOrCreate works correctly', function (): void {
    // Create a nameserver
    $nameserver1 = Nameserver::firstOrCreate(
        ['name' => 'ns1.test.com'],
        [
            'uuid' => Str::uuid(),
            'type' => 'default',
            'priority' => 1,
            'status' => 'active',
        ]
    );

    expect($nameserver1->name)->toBe('ns1.test.com');
    expect(Nameserver::count())->toBe(1);

    // Try to create the same nameserver again - should return the existing one
    $nameserver2 = Nameserver::firstOrCreate(
        ['name' => 'ns1.test.com'],
        [
            'uuid' => Str::uuid(),
            'type' => 'default',
            'priority' => 1,
            'status' => 'active',
        ]
    );

    expect($nameserver2->id)->toBe($nameserver1->id);
    expect(Nameserver::count())->toBe(1); // Still only one nameserver
});

test('domain nameserver association can be updated', function (): void {
    // Create initial nameservers
    $oldNameserver = Nameserver::factory()->create(['name' => 'old.nameserver.com']);
    $this->domain->nameservers()->attach($oldNameserver->id);

    // Verify initial state
    expect($this->domain->nameservers)->toHaveCount(1);
    expect($this->domain->nameservers->first()->name)->toBe('old.nameserver.com');

    // Update to new nameservers
    $newNameserver1 = Nameserver::factory()->create(['name' => 'new1.nameserver.com']);
    $newNameserver2 = Nameserver::factory()->create(['name' => 'new2.nameserver.com']);

    // Remove old and add new associations
    $this->domain->nameservers()->detach();
    $this->domain->nameservers()->attach([$newNameserver1->id, $newNameserver2->id]);
    $this->domain->refresh();

    // Verify new state
    expect($this->domain->nameservers)->toHaveCount(2);
    expect($this->domain->nameservers->pluck('name')->toArray())
        ->toContain('new1.nameserver.com', 'new2.nameserver.com')
        ->not->toContain('old.nameserver.com');
});

test('empty nameserver names are handled correctly', function (): void {
    $nameservers = ['ns1.example.com', '', '   ', 'ns2.example.com'];
    $validNameservers = [];

    foreach ($nameservers as $nameserverName) {
        $nameserverName = mb_trim($nameserverName);
        if ($nameserverName !== '' && $nameserverName !== '0') {
            $validNameservers[] = $nameserverName;
        }
    }

    expect($validNameservers)->toHaveCount(2);
    expect($validNameservers)->toContain('ns1.example.com', 'ns2.example.com');
});

test('domain type detection works correctly', function (): void {
    $localDomains = [
        'example.rw',
        'test.example.rw',
        'subdomain.test.example.rw',
    ];

    $internationalDomains = [
        'example.com',
        'test.org',
        'subdomain.example.net',
        'example.rw.com', // This should NOT be considered local
    ];

    foreach ($localDomains as $domainName) {
        $isLocal = str_ends_with(mb_strtolower($domainName), '.rw');
        expect($isLocal)->toBeTrue("Domain {$domainName} should be considered local");
    }

    foreach ($internationalDomains as $domainName) {
        $isLocal = str_ends_with(mb_strtolower($domainName), '.rw');
        expect($isLocal)->toBeFalse("Domain {$domainName} should not be considered local");
    }
});
