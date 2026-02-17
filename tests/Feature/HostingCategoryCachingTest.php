<?php

declare(strict_types=1);

use App\Models\HostingCategory;
use Illuminate\Support\Facades\Cache;

it('returns active hosting categories from cache', function (): void {
    Cache::forget('active_hosting_categories');

    HostingCategory::factory()->create(['name' => 'Shared Hosting', 'status' => 'active']);
    HostingCategory::factory()->create(['name' => 'VPS Hosting', 'status' => 'active']);

    $categories = HostingCategory::getActiveCategories();

    expect($categories)->toHaveCount(2);
    expect(Cache::has('active_hosting_categories'))->toBeTrue();
});

it('serves categories from cache on subsequent calls', function (): void {
    Cache::forget('active_hosting_categories');

    HostingCategory::factory()->create(['status' => 'active']);

    $firstCall = HostingCategory::getActiveCategories();
    $secondCall = HostingCategory::getActiveCategories();

    expect($firstCall->pluck('id')->toArray())->toBe($secondCall->pluck('id')->toArray());
});

it('does not include inactive categories', function (): void {
    Cache::forget('active_hosting_categories');

    HostingCategory::factory()->create(['name' => 'Active Category', 'status' => 'active']);
    HostingCategory::factory()->create(['name' => 'Inactive Category', 'status' => 'inactive']);

    $categories = HostingCategory::getActiveCategories();

    expect($categories)->toHaveCount(1)
        ->and($categories->first()->name)->toBe('Active Category');
});

it('orders categories by name', function (): void {
    Cache::forget('active_hosting_categories');

    HostingCategory::factory()->create(['name' => 'Zebra Hosting', 'status' => 'active']);
    HostingCategory::factory()->create(['name' => 'Alpha Hosting', 'status' => 'active']);

    $categories = HostingCategory::getActiveCategories();

    expect($categories->first()->name)->toBe('Alpha Hosting')
        ->and($categories->last()->name)->toBe('Zebra Hosting');
});
