<?php

declare(strict_types=1);

use App\Enums\TldStatus;
use App\Enums\TldType;
use App\Models\Tld;
use App\Services\Domain\NamecheapDomainService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Mockery\MockInterface;

uses(RefreshDatabase::class);

test('import creates TLDs from namecheap response and new ones are inactive by default', function (): void {
    $this->mock(NamecheapDomainService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('getTldList')
            ->once()
            ->andReturn([
                'success' => true,
                'tlds' => [
                    ['name' => 'com', 'prices' => []],
                    ['name' => 'net', 'prices' => []],
                    ['name' => 'org', 'prices' => []],
                ],
            ]);
    });

    $this->artisan('app:import-namecheap-tlds')
        ->assertSuccessful();

    expect(Tld::query()->count())->toBe(3);
    $com = Tld::query()->where('name', '.com')->first();
    expect($com)->not->toBeNull()
        ->and($com->status)->toBe(TldStatus::Inactive)
        ->and($com->type->value)->toBe('international');
});

test('import with activate option marks new TLDs as active', function (): void {
    $this->mock(NamecheapDomainService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('getTldList')
            ->once()
            ->andReturn([
                'success' => true,
                'tlds' => [['name' => 'io', 'prices' => []]],
            ]);
    });

    $this->artisan('app:import-namecheap-tlds', ['--activate' => true])
        ->assertSuccessful();

    $tld = Tld::query()->where('name', '.io')->first();
    expect($tld)->not->toBeNull()
        ->and($tld->status)->toBe(TldStatus::Active);
});

test('import does not duplicate existing TLDs and activates them when option given', function (): void {
    Tld::query()->create([
        'uuid' => (string) Str::uuid(),
        'name' => '.com',
        'type' => TldType::International,
        'status' => TldStatus::Inactive,
    ]);

    $this->mock(NamecheapDomainService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('getTldList')
            ->once()
            ->andReturn([
                'success' => true,
                'tlds' => [
                    ['name' => 'com', 'prices' => []],
                    ['name' => 'net', 'prices' => []],
                ],
            ]);
    });

    $this->artisan('app:import-namecheap-tlds', ['--activate' => true])
        ->assertSuccessful();

    expect(Tld::query()->count())->toBe(2);
    $com = Tld::query()->where('name', '.com')->first();
    expect($com->status)->toBe(TldStatus::Active);
});

test('import exits with error when getTldList returns success false', function (): void {
    $this->mock(NamecheapDomainService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('getTldList')
            ->once()
            ->andReturn(['success' => false, 'message' => 'API error']);
    });

    $this->artisan('app:import-namecheap-tlds')
        ->assertFailed();
});

test('import exits with error when getTldList throws', function (): void {
    $this->mock(NamecheapDomainService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('getTldList')
            ->once()
            ->andThrow(new RuntimeException('Network error'));
    });

    $this->artisan('app:import-namecheap-tlds')
        ->assertFailed();
});
