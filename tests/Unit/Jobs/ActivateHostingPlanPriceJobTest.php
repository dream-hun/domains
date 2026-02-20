<?php

declare(strict_types=1);

use App\Actions\Hosting\PlanPrices\ActivateHostingPlanPriceAction;
use App\Jobs\ActivateHostingPlanPriceJob;
use App\Models\HostingPlanPrice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

test('job activates price when found', function (): void {
    $plan = App\Models\HostingPlan::factory()->create();
    $currency = App\Models\Currency::factory()->create();

    $price = HostingPlanPrice::factory()->create([
        'hosting_plan_id' => $plan->id,
        'currency_id' => $currency->id,
        'is_current' => false,
        'effective_date' => now()->subDay(),
    ]);

    $job = new ActivateHostingPlanPriceJob($price->uuid);
    $action = Mockery::mock(ActivateHostingPlanPriceAction::class);
    $action->shouldReceive('handle')
        ->once()
        ->with(Mockery::on(fn ($arg) => $arg->id === $price->id));

    $job->handle($action);

    Mockery::close();
});

test('job logs warning when price not found', function (): void {
    Log::shouldReceive('warning')
        ->once()
        ->with('ActivateHostingPlanPriceJob: Price not found', ['uuid' => 'non-existent-uuid']);

    $job = new ActivateHostingPlanPriceJob('non-existent-uuid');
    $action = Mockery::mock(ActivateHostingPlanPriceAction::class);
    $action->shouldNotReceive('handle');

    $job->handle($action);

    Mockery::close();
});

test('job logs info and returns early when price already current', function (): void {
    $plan = App\Models\HostingPlan::factory()->create();
    $currency = App\Models\Currency::factory()->create();

    $price = HostingPlanPrice::factory()->create([
        'hosting_plan_id' => $plan->id,
        'currency_id' => $currency->id,
        'is_current' => true,
    ]);

    Log::shouldReceive('info')
        ->once()
        ->with('ActivateHostingPlanPriceJob: Price already current', ['uuid' => $price->uuid]);

    $job = new ActivateHostingPlanPriceJob($price->uuid);
    $action = Mockery::mock(ActivateHostingPlanPriceAction::class);
    $action->shouldNotReceive('handle');

    $job->handle($action);

    Mockery::close();
});

test('job logs error and throws exception on failure', function (): void {
    $plan = App\Models\HostingPlan::factory()->create();
    $currency = App\Models\Currency::factory()->create();

    $price = HostingPlanPrice::factory()->create([
        'hosting_plan_id' => $plan->id,
        'currency_id' => $currency->id,
        'is_current' => false,
    ]);

    $exception = new Exception('Test exception');

    Log::shouldReceive('info')
        ->once()
        ->with('ActivateHostingPlanPriceJob: Activating price', Mockery::type('array'));

    Log::shouldReceive('error')
        ->once()
        ->with('ActivateHostingPlanPriceJob: Failed to activate price', Mockery::type('array'));

    $job = new ActivateHostingPlanPriceJob($price->uuid);
    $action = Mockery::mock(ActivateHostingPlanPriceAction::class);
    $action->shouldReceive('handle')
        ->once()
        ->andThrow($exception);

    expect(fn () => $job->handle($action))->toThrow(Exception::class, 'Test exception');

    Mockery::close();
});
