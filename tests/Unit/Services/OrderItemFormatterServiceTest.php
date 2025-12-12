<?php

declare(strict_types=1);

use App\Enums\Hosting\BillingCycle;
use App\Models\Currency;
use App\Models\HostingPlan;
use App\Models\OrderItem;
use App\Models\Role;
use App\Services\OrderItemFormatterService;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::query()->create(['id' => 1, 'title' => 'Admin']);
    Role::query()->create(['id' => 2, 'title' => 'User']);

    Currency::query()->create([
        'code' => 'USD',
        'name' => 'US Dollar',
        'symbol' => '$',
        'exchange_rate' => 1.0,
        'is_base' => true,
        'is_active' => true,
    ]);

    $this->formatter = new OrderItemFormatterService();
});

describe('formatBillingCycleLabel', function (): void {
    it('formats monthly billing cycle correctly', function (): void {
        $result = $this->formatter->formatBillingCycleLabel(BillingCycle::Monthly);

        expect($result)->toBe('1 month');
    });

    it('formats quarterly billing cycle correctly', function (): void {
        $result = $this->formatter->formatBillingCycleLabel(BillingCycle::Quarterly);

        expect($result)->toBe('3 months');
    });

    it('formats annually billing cycle correctly', function (): void {
        $result = $this->formatter->formatBillingCycleLabel(BillingCycle::Annually);

        expect($result)->toBe('1 year');
    });

    it('formats biennially billing cycle correctly', function (): void {
        $result = $this->formatter->formatBillingCycleLabel(BillingCycle::Biennially);

        expect($result)->toBe('2 years');
    });
});

describe('formatDurationLabel', function (): void {
    it('formats months less than 12 correctly', function (): void {
        expect($this->formatter->formatDurationLabel(1))->toBe('1 month');
        expect($this->formatter->formatDurationLabel(6))->toBe('6 months');
        expect($this->formatter->formatDurationLabel(11))->toBe('11 months');
    });

    it('formats years correctly', function (): void {
        expect($this->formatter->formatDurationLabel(12))->toBe('1 year');
        expect($this->formatter->formatDurationLabel(24))->toBe('2 years');
        expect($this->formatter->formatDurationLabel(36))->toBe('3 years');
    });
});

describe('getItemDisplayName with OrderItem', function (): void {
    it('returns plan name for subscription renewal with hosting plan id', function (): void {
        $plan = HostingPlan::factory()->create(['name' => 'Premium Plan']);

        $orderItem = OrderItem::factory()->create([
            'domain_type' => 'subscription_renewal',
            'domain_name' => 'example.com - Premium Plan (Renewal)',
            'metadata' => ['hosting_plan_id' => $plan->id],
        ]);

        $result = $this->formatter->getItemDisplayName($orderItem);

        expect($result)->toBe('Premium Plan');
    });

    it('returns plan name for hosting item with hosting plan id', function (): void {
        $plan = HostingPlan::factory()->create(['name' => 'Basic Plan']);

        $orderItem = OrderItem::factory()->create([
            'domain_type' => 'hosting',
            'domain_name' => 'example.com Hosting',
            'metadata' => ['hosting_plan_id' => $plan->id],
        ]);

        $result = $this->formatter->getItemDisplayName($orderItem);

        expect($result)->toBe('Basic Plan');
    });

    it('extracts plan name from domain name for subscription renewal', function (): void {
        $orderItem = OrderItem::factory()->create([
            'domain_type' => 'subscription_renewal',
            'domain_name' => 'example.com - Premium Plan (Renewal)',
            'metadata' => [],
        ]);

        $result = $this->formatter->getItemDisplayName($orderItem);

        expect($result)->toBe('Premium Plan');
    });

    it('returns domain name for registration items', function (): void {
        $orderItem = OrderItem::factory()->create([
            'domain_type' => 'registration',
            'domain_name' => 'example.com',
        ]);

        $result = $this->formatter->getItemDisplayName($orderItem);

        expect($result)->toBe('example.com');
    });

    it('returns domain name for renewal items', function (): void {
        $orderItem = OrderItem::factory()->create([
            'domain_type' => 'renewal',
            'domain_name' => 'example.com',
        ]);

        $result = $this->formatter->getItemDisplayName($orderItem);

        expect($result)->toBe('example.com');
    });

    it('returns fallback for invalid items', function (): void {
        $orderItem = OrderItem::factory()->create([
            'domain_type' => 'subscription_renewal',
            'domain_name' => 'N/A',
            'metadata' => [],
        ]);

        $result = $this->formatter->getItemDisplayName($orderItem);

        expect($result)->toBe('Item');
    });
});

describe('getItemPeriod with OrderItem', function (): void {
    it('formats subscription renewal period using duration_months', function (): void {
        $orderItem = OrderItem::factory()->create([
            'domain_type' => 'subscription_renewal',
            'quantity' => 6,
            'metadata' => ['duration_months' => 6],
        ]);

        $result = $this->formatter->getItemPeriod($orderItem);

        expect($result)->toBe('6 months renewal');
    });

    it('formats subscription renewal period using billing_cycle', function (): void {
        $orderItem = OrderItem::factory()->create([
            'domain_type' => 'subscription_renewal',
            'metadata' => ['billing_cycle' => BillingCycle::Monthly->value],
        ]);

        $result = $this->formatter->getItemPeriod($orderItem);

        expect($result)->toBe('1 month renewal');
    });

    it('formats hosting period using billing_cycle', function (): void {
        $orderItem = OrderItem::factory()->create([
            'domain_type' => 'hosting',
            'metadata' => ['billing_cycle' => BillingCycle::Annually->value],
        ]);

        $result = $this->formatter->getItemPeriod($orderItem);

        expect($result)->toBe('1 year');
    });

    it('formats domain renewal period using years', function (): void {
        $orderItem = OrderItem::factory()->create([
            'domain_type' => 'renewal',
            'years' => 2,
        ]);

        $result = $this->formatter->getItemPeriod($orderItem);

        expect($result)->toBe('2 years renewal');
    });

    it('formats domain registration period using years', function (): void {
        $orderItem = OrderItem::factory()->create([
            'domain_type' => 'registration',
            'years' => 1,
        ]);

        $result = $this->formatter->getItemPeriod($orderItem);

        expect($result)->toBe('1 year of registration');
    });
});

describe('getItemDisplayName with cart items', function (): void {
    it('returns plan name for cart item with hosting plan id', function (): void {
        $plan = HostingPlan::factory()->create(['name' => 'Premium Plan']);

        Cart::add([
            'id' => 'test-item',
            'name' => 'example.com - Premium Plan',
            'price' => 10.00,
            'quantity' => 1,
            'attributes' => [
                'type' => 'subscription_renewal',
                'hosting_plan_id' => $plan->id,
            ],
        ]);

        $item = Cart::get('test-item');
        $result = $this->formatter->getItemDisplayName($item);

        expect($result)->toBe('Premium Plan');
        Cart::clear();
    });

    it('returns domain name for cart registration item', function (): void {
        Cart::add([
            'id' => 'test-domain',
            'name' => 'example.com',
            'price' => 10.00,
            'quantity' => 1,
            'attributes' => [
                'type' => 'registration',
            ],
        ]);

        $item = Cart::get('test-domain');
        $result = $this->formatter->getItemDisplayName($item);

        expect($result)->toBe('example.com');
        Cart::clear();
    });
});

describe('getItemPeriod with cart items', function (): void {
    it('formats cart item period for subscription renewal', function (): void {
        Cart::add([
            'id' => 'test-renewal',
            'name' => 'Subscription Renewal',
            'price' => 10.00,
            'quantity' => 3,
            'attributes' => [
                'type' => 'subscription_renewal',
                'duration_months' => 3,
            ],
        ]);

        $item = Cart::get('test-renewal');
        $result = $this->formatter->getItemPeriod($item);

        expect($result)->toBe('3 months renewal');
        Cart::clear();
    });

    it('formats cart item period for domain renewal', function (): void {
        Cart::add([
            'id' => 'test-domain-renewal',
            'name' => 'example.com',
            'price' => 10.00,
            'quantity' => 2,
            'attributes' => [
                'type' => 'renewal',
            ],
        ]);

        $item = Cart::get('test-domain-renewal');
        $result = $this->formatter->getItemPeriod($item);

        expect($result)->toBe('2 years renewal');
        Cart::clear();
    });
});
