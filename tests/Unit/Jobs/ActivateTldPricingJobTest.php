<?php

declare(strict_types=1);

use App\Actions\TldPricing\ActivateTldPricingAction;
use App\Jobs\ActivateTldPricingJob;
use App\Models\Currency;
use App\Models\Tld;
use App\Models\TldPricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

test('job activates pricing when found', function (): void {
    $tld = Tld::factory()->create();
    $currency = Currency::factory()->create();

    $pricing = TldPricing::factory()->create([
        'tld_id' => $tld->id,
        'currency_id' => $currency->id,
        'is_current' => false,
        'effective_date' => now()->subDay(),
    ]);

    $job = new ActivateTldPricingJob($pricing->uuid);
    $action = new ActivateTldPricingAction();

    $job->handle($action);

    $pricing->refresh();
    expect($pricing->is_current)->toBeTrue();
});

test('job logs warning when pricing not found', function (): void {
    Log::shouldReceive('warning')
        ->once()
        ->with('ActivateTldPricingJob: Pricing not found', ['uuid' => 'non-existent-uuid']);

    $job = new ActivateTldPricingJob('non-existent-uuid');
    $action = new ActivateTldPricingAction();

    $job->handle($action);
});

test('job logs info and returns early when pricing already current', function (): void {
    $tld = Tld::factory()->create();
    $currency = Currency::factory()->create();

    $pricing = TldPricing::factory()->create([
        'tld_id' => $tld->id,
        'currency_id' => $currency->id,
        'is_current' => true,
    ]);

    Log::shouldReceive('info')
        ->once()
        ->with('ActivateTldPricingJob: Pricing already current', ['uuid' => $pricing->uuid]);

    $job = new ActivateTldPricingJob($pricing->uuid);
    $action = new ActivateTldPricingAction();

    $job->handle($action);

    $pricing->refresh();
    expect($pricing->is_current)->toBeTrue();
});

test('job logs success when activation completes', function (): void {
    $tld = Tld::factory()->create();
    $currency = Currency::factory()->create();

    $pricing = TldPricing::factory()->create([
        'tld_id' => $tld->id,
        'currency_id' => $currency->id,
        'is_current' => false,
        'effective_date' => now()->subDay(),
    ]);

    Log::shouldReceive('info')
        ->twice()
        ->with(Mockery::pattern('/ActivateTldPricingJob:/'), Mockery::type('array'));

    $job = new ActivateTldPricingJob($pricing->uuid);
    $action = new ActivateTldPricingAction();

    $job->handle($action);

    $pricing->refresh();
    expect($pricing->is_current)->toBeTrue();
});
