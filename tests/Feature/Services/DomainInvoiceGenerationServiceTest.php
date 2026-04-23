<?php

declare(strict_types=1);

use App\Jobs\GenerateDomainRenewalInvoiceJob;
use App\Models\Domain;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\DomainInvoiceGenerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

test('dispatches renewal invoice job for domain expiring within window', function (): void {
    Date::setTestNow(Date::create(2026, 3, 14));
    Queue::fake();

    $user = User::factory()->create();
    $domain = Domain::factory()->create([
        'owner_id' => $user->id,
        'status' => 'active',
        'auto_renew' => true,
        'expires_at' => Date::now()->addDays(5),
        'years' => 1,
    ]);

    $service = new DomainInvoiceGenerationService;
    $results = $service->generateRenewalInvoices(7);

    expect($results['dispatched'])->toBe(1)
        ->and($results['skipped'])->toBe(0);

    Queue::assertPushed(GenerateDomainRenewalInvoiceJob::class, fn ($job): bool => $job->domain->id === $domain->id);
});

test('skips domain with existing pending renewal order', function (): void {
    Date::setTestNow(Date::create(2026, 3, 14));
    Queue::fake();

    $user = User::factory()->create();
    $domain = Domain::factory()->create([
        'owner_id' => $user->id,
        'status' => 'active',
        'auto_renew' => true,
        'expires_at' => Date::now()->addDays(5),
        'years' => 1,
    ]);

    $existingOrder = Order::factory()->pending()->create([
        'user_id' => $user->id,
    ]);
    OrderItem::factory()->create([
        'order_id' => $existingOrder->id,
        'domain_name' => $domain->name,
        'domain_type' => 'renewal',
    ]);

    $service = new DomainInvoiceGenerationService;
    $results = $service->generateRenewalInvoices(7);

    expect($results['dispatched'])->toBe(0)
        ->and($results['skipped'])->toBe(1);

    Queue::assertNothingPushed();
});

test('skips domains without auto renew', function (): void {
    Date::setTestNow(Date::create(2026, 3, 14));
    Queue::fake();

    $user = User::factory()->create();
    Domain::factory()->create([
        'owner_id' => $user->id,
        'status' => 'active',
        'auto_renew' => false,
        'expires_at' => Date::now()->addDays(5),
    ]);

    $service = new DomainInvoiceGenerationService;
    $results = $service->generateRenewalInvoices(7);

    expect($results['dispatched'])->toBe(0);

    Queue::assertNothingPushed();
});

test('skips non-active domains', function (): void {
    Date::setTestNow(Date::create(2026, 3, 14));
    Queue::fake();

    $user = User::factory()->create();
    Domain::factory()->create([
        'owner_id' => $user->id,
        'status' => 'expired',
        'auto_renew' => true,
        'expires_at' => Date::now()->addDays(5),
    ]);

    $service = new DomainInvoiceGenerationService;
    $results = $service->generateRenewalInvoices(7);

    expect($results['dispatched'])->toBe(0);

    Queue::assertNothingPushed();
});

test('skips domains expiring outside the window', function (): void {
    Date::setTestNow(Date::create(2026, 3, 14));
    Queue::fake();

    $user = User::factory()->create();
    Domain::factory()->create([
        'owner_id' => $user->id,
        'status' => 'active',
        'auto_renew' => true,
        'expires_at' => Date::now()->addDays(15),
    ]);

    $service = new DomainInvoiceGenerationService;
    $results = $service->generateRenewalInvoices(7);

    expect($results['dispatched'])->toBe(0);

    Queue::assertNothingPushed();
});
