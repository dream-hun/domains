<?php

declare(strict_types=1);

use App\Actions\RegisterDomainAction;
use App\Models\Contact;
use App\Models\Currency;
use App\Models\Domain;
use App\Models\DomainPrice;
use App\Models\HostingPlan;
use App\Models\HostingPlanPrice;
use App\Models\Order;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Create admin role and permissions
    $adminRole = Role::query()->firstOrCreate(['title' => 'Admin']);
    $userRole = Role::query()->firstOrCreate(['title' => 'User']);

    $domainCreatePermission = Permission::query()->firstOrCreate(['title' => 'domain_create']);
    $domainAccessPermission = Permission::query()->firstOrCreate(['title' => 'domain_access']);

    $adminRole->permissions()->syncWithoutDetaching([
        $domainCreatePermission->id,
        $domainAccessPermission->id,
    ]);

    $this->admin = User::factory()->create();
    $this->admin->roles()->attach($adminRole);

    $this->regularUser = User::factory()->create();
    $this->regularUser->roles()->attach($userRole);

    // Create currencies
    Currency::query()->create([
        'code' => 'USD',
        'name' => 'US Dollar',
        'symbol' => '$',
        'exchange_rate' => 1.0,
        'is_base' => true,
        'is_active' => true,
    ]);

    Currency::query()->create([
        'code' => 'RWF',
        'name' => 'Rwandan Franc',
        'symbol' => 'Fr',
        'exchange_rate' => 1200.0,
        'is_base' => false,
        'is_active' => true,
    ]);

    // Create domain price for .com
    $this->domainPrice = DomainPrice::factory()->international()->create([
        'tld' => '.com',
        'register_price' => 1200, // $12.00
        'renewal_price' => 1200,
    ]);

    // Create contacts
    $this->contact = Contact::factory()->create(['user_id' => $this->regularUser->id]);
});

test('admin can access custom domain registration form', function (): void {
    $this->actingAs($this->admin)
        ->get(route('admin.domains.custom-register'))
        ->assertSuccessful()
        ->assertSee('Custom Domain Registration');
});

test('non-admin cannot access custom domain registration form', function (): void {
    $this->actingAs($this->regularUser)
        ->get(route('admin.domains.custom-register'))
        ->assertForbidden();
});

test('admin can register domain with custom price', function (): void {
    // Mock the RegisterDomainAction to avoid actual API calls
    $this->mock(RegisterDomainAction::class, function ($mock): void {
        $mock->shouldReceive('handle')
            ->once()
            ->andReturnUsing(function ($domainName, $contacts, $years, $nameservers, $useSingleContact, $userId): array {
                $domain = Domain::factory()->create([
                    'name' => $domainName,
                    'owner_id' => $userId,
                    'domain_price_id' => $this->domainPrice->id,
                    'years' => $years,
                ]);

                return [
                    'success' => true,
                    'domain_id' => $domain->id,
                    'message' => 'Domain registered successfully',
                ];
            });
    });

    $data = [
        'domain_name' => 'testcustom.com',
        'user_id' => $this->regularUser->id,
        'years' => 2,
        'registrant_contact_id' => $this->contact->id,
        'admin_contact_id' => $this->contact->id,
        'technical_contact_id' => $this->contact->id,
        'billing_contact_id' => $this->contact->id,
        'domain_custom_price' => 25.00,
        'domain_custom_price_currency' => 'USD',
        'domain_custom_price_notes' => 'Special VIP pricing',
        'subscription_option' => 'none',
    ];

    $this->actingAs($this->admin)
        ->post(route('admin.domains.custom-register.store'), $data)
        ->assertRedirect(route('admin.domains.index'))
        ->assertSessionHas('success');

    $domain = Domain::query()->where('name', 'testcustom.com')->first();

    expect($domain)->not->toBeNull()
        ->and($domain->is_custom_price)->toBeTrue()
        ->and((float) $domain->custom_price)->toBe(25.00)
        ->and($domain->custom_price_currency)->toBe('USD')
        ->and($domain->custom_price_notes)->toBe('Special VIP pricing')
        ->and($domain->created_by_admin_id)->toBe($this->admin->id);

    // Verify order was created
    $order = Order::query()->where('user_id', $this->regularUser->id)->first();
    expect($order)->not->toBeNull()
        ->and($order->type)->toBe('custom_registration')
        ->and($order->payment_status)->toBe('manual')
        ->and($order->status)->toBe('completed')
        ->and((float) $order->total_amount)->toBe(25.00)
        ->and($order->currency)->toBe('USD')
        ->and($order->metadata['created_by_admin_id'])->toBe($this->admin->id);

    // Verify order item was created
    expect($order->orderItems)->toHaveCount(1);
    $orderItem = $order->orderItems->first();
    expect($orderItem->domain_name)->toBe('testcustom.com')
        ->and($orderItem->domain_type)->toBe('custom_registration')
        ->and((float) $orderItem->price)->toBe(25.00);
});

test('admin can register domain and create new subscription', function (): void {
    $plan = HostingPlan::factory()->create(['name' => 'Premium Plan']);
    $planPrice = HostingPlanPrice::factory()->create([
        'hosting_plan_id' => $plan->id,
        'billing_cycle' => 'monthly',
        'renewal_price' => 10000,
    ]);

    $this->mock(RegisterDomainAction::class, function ($mock): void {
        $mock->shouldReceive('handle')
            ->once()
            ->andReturnUsing(function ($domainName, $contacts, $years, $nameservers, $useSingleContact, $userId): array {
                $domain = Domain::factory()->create([
                    'name' => $domainName,
                    'owner_id' => $userId,
                    'domain_price_id' => $this->domainPrice->id,
                    'years' => $years,
                ]);

                return [
                    'success' => true,
                    'domain_id' => $domain->id,
                    'message' => 'Domain registered successfully',
                ];
            });
    });

    $data = [
        'domain_name' => 'withsubscription.com',
        'user_id' => $this->regularUser->id,
        'years' => 1,
        'registrant_contact_id' => $this->contact->id,
        'admin_contact_id' => $this->contact->id,
        'technical_contact_id' => $this->contact->id,
        'billing_contact_id' => $this->contact->id,
        'domain_custom_price' => 30.00,
        'domain_custom_price_currency' => 'USD',
        'subscription_option' => 'create_new',
        'hosting_plan_id' => $plan->id,
        'billing_cycle' => 'monthly',
        'hosting_starts_at' => now()->format('Y-m-d'),
        'hosting_expires_at' => now()->addMonth()->format('Y-m-d'),
        'hosting_auto_renew' => true,
        'hosting_custom_price' => 50.00,
        'hosting_custom_price_currency' => 'USD',
        'hosting_custom_price_notes' => 'Bundled discount',
    ];

    $this->actingAs($this->admin)
        ->post(route('admin.domains.custom-register.store'), $data)
        ->assertRedirect(route('admin.domains.index'))
        ->assertSessionHas('success');

    $domain = Domain::query()->where('name', 'withsubscription.com')->first();
    $subscription = Subscription::query()->where('domain', 'withsubscription.com')->first();

    expect($domain)->not->toBeNull()
        ->and($subscription)->not->toBeNull()
        ->and($domain->subscription_id)->toBe($subscription->id)
        ->and($subscription->is_custom_price)->toBeTrue()
        ->and((float) $subscription->custom_price)->toBe(50.00)
        ->and($subscription->custom_price_notes)->toBe('Bundled discount')
        ->and($subscription->auto_renew)->toBeTrue();

    // Verify orders were created (one for domain, one for subscription)
    $orders = Order::query()->where('user_id', $this->regularUser->id)->get();
    expect($orders)->toHaveCount(2);

    $domainOrder = $orders->firstWhere('type', 'custom_registration');
    expect($domainOrder)->not->toBeNull()
        ->and((float) $domainOrder->total_amount)->toBe(30.00);

    $subscriptionOrder = $orders->firstWhere('type', 'custom_subscription');
    expect($subscriptionOrder)->not->toBeNull()
        ->and((float) $subscriptionOrder->total_amount)->toBe(50.00);
});

test('admin can register domain and link to existing subscription', function (): void {
    $plan = HostingPlan::factory()->create();
    $planPrice = HostingPlanPrice::factory()->create([
        'hosting_plan_id' => $plan->id,
        'billing_cycle' => 'monthly',
    ]);

    $existingSubscription = Subscription::factory()->create([
        'user_id' => $this->regularUser->id,
        'hosting_plan_id' => $plan->id,
        'hosting_plan_price_id' => $planPrice->id,
        'domain' => null, // No domain linked yet
        'status' => 'active',
    ]);

    $this->mock(RegisterDomainAction::class, function ($mock): void {
        $mock->shouldReceive('handle')
            ->once()
            ->andReturnUsing(function ($domainName, $contacts, $years, $nameservers, $useSingleContact, $userId): array {
                $domain = Domain::factory()->create([
                    'name' => $domainName,
                    'owner_id' => $userId,
                    'domain_price_id' => $this->domainPrice->id,
                    'years' => $years,
                ]);

                return [
                    'success' => true,
                    'domain_id' => $domain->id,
                    'message' => 'Domain registered successfully',
                ];
            });
    });

    $data = [
        'domain_name' => 'linkedomain.com',
        'user_id' => $this->regularUser->id,
        'years' => 1,
        'registrant_contact_id' => $this->contact->id,
        'admin_contact_id' => $this->contact->id,
        'technical_contact_id' => $this->contact->id,
        'billing_contact_id' => $this->contact->id,
        'domain_custom_price' => 15.00,
        'domain_custom_price_currency' => 'USD',
        'subscription_option' => 'link_existing',
        'existing_subscription_id' => $existingSubscription->id,
    ];

    $this->actingAs($this->admin)
        ->post(route('admin.domains.custom-register.store'), $data)
        ->assertRedirect(route('admin.domains.index'))
        ->assertSessionHas('success');

    $domain = Domain::query()->where('name', 'linkedomain.com')->first();
    $existingSubscription->refresh();

    expect($domain)->not->toBeNull()
        ->and($domain->subscription_id)->toBe($existingSubscription->id)
        ->and($existingSubscription->domain)->toBe('linkedomain.com');

    // Verify order was created for the domain
    $order = Order::query()->where('user_id', $this->regularUser->id)->first();
    expect($order)->not->toBeNull()
        ->and($order->type)->toBe('custom_registration')
        ->and((float) $order->total_amount)->toBe(15.00);
});

test('validation requires domain name', function (): void {
    $data = [
        'user_id' => $this->regularUser->id,
        'years' => 1,
        'registrant_contact_id' => $this->contact->id,
        'admin_contact_id' => $this->contact->id,
        'technical_contact_id' => $this->contact->id,
        'billing_contact_id' => $this->contact->id,
        'domain_custom_price' => 25.00,
        'domain_custom_price_currency' => 'USD',
        'subscription_option' => 'none',
    ];

    $this->actingAs($this->admin)
        ->post(route('admin.domains.custom-register.store'), $data)
        ->assertSessionHasErrors('domain_name');
});

test('validation requires custom price and currency', function (): void {
    $data = [
        'domain_name' => 'needsprice.com',
        'user_id' => $this->regularUser->id,
        'years' => 1,
        'registrant_contact_id' => $this->contact->id,
        'admin_contact_id' => $this->contact->id,
        'technical_contact_id' => $this->contact->id,
        'billing_contact_id' => $this->contact->id,
        // Missing domain_custom_price and domain_custom_price_currency
        'subscription_option' => 'none',
    ];

    $this->actingAs($this->admin)
        ->post(route('admin.domains.custom-register.store'), $data)
        ->assertSessionHasErrors(['domain_custom_price', 'domain_custom_price_currency']);
});

test('validation requires hosting plan when creating new subscription', function (): void {
    $data = [
        'domain_name' => 'needsplan.com',
        'user_id' => $this->regularUser->id,
        'years' => 1,
        'registrant_contact_id' => $this->contact->id,
        'admin_contact_id' => $this->contact->id,
        'technical_contact_id' => $this->contact->id,
        'billing_contact_id' => $this->contact->id,
        'domain_custom_price' => 25.00,
        'domain_custom_price_currency' => 'USD',
        'subscription_option' => 'create_new',
        // Missing hosting_plan_id
        'billing_cycle' => 'monthly',
        'hosting_starts_at' => now()->format('Y-m-d'),
        'hosting_expires_at' => now()->addMonth()->format('Y-m-d'),
    ];

    $this->actingAs($this->admin)
        ->post(route('admin.domains.custom-register.store'), $data)
        ->assertSessionHasErrors('hosting_plan_id');
});

test('validation requires existing subscription when linking', function (): void {
    $data = [
        'domain_name' => 'needssub.com',
        'user_id' => $this->regularUser->id,
        'years' => 1,
        'registrant_contact_id' => $this->contact->id,
        'admin_contact_id' => $this->contact->id,
        'technical_contact_id' => $this->contact->id,
        'billing_contact_id' => $this->contact->id,
        'domain_custom_price' => 25.00,
        'domain_custom_price_currency' => 'USD',
        'subscription_option' => 'link_existing',
        // Missing existing_subscription_id
    ];

    $this->actingAs($this->admin)
        ->post(route('admin.domains.custom-register.store'), $data)
        ->assertSessionHasErrors('existing_subscription_id');
});

test('domain model has custom price helpers', function (): void {
    $domainWithCustomPrice = Domain::factory()->create([
        'is_custom_price' => true,
        'custom_price' => 99.99,
        'custom_price_currency' => 'USD',
    ]);

    $domainWithoutCustomPrice = Domain::factory()->create([
        'is_custom_price' => false,
        'custom_price' => null,
    ]);

    expect($domainWithCustomPrice->isCustomPriced())->toBeTrue()
        ->and($domainWithCustomPrice->getCustomPrice())->toBe(99.99)
        ->and($domainWithoutCustomPrice->isCustomPriced())->toBeFalse()
        ->and($domainWithoutCustomPrice->getCustomPrice())->toBeNull();
});

test('domain has subscription relationship', function (): void {
    $plan = HostingPlan::factory()->create();
    $planPrice = HostingPlanPrice::factory()->create([
        'hosting_plan_id' => $plan->id,
        'billing_cycle' => 'monthly',
    ]);

    $subscription = Subscription::factory()->create([
        'hosting_plan_id' => $plan->id,
        'hosting_plan_price_id' => $planPrice->id,
    ]);

    $domain = Domain::factory()->create([
        'subscription_id' => $subscription->id,
    ]);

    expect($domain->subscription)->not->toBeNull()
        ->and($domain->subscription->id)->toBe($subscription->id);
});

test('subscription has linked domain relationship', function (): void {
    $plan = HostingPlan::factory()->create();
    $planPrice = HostingPlanPrice::factory()->create([
        'hosting_plan_id' => $plan->id,
        'billing_cycle' => 'monthly',
    ]);

    $subscription = Subscription::factory()->create([
        'hosting_plan_id' => $plan->id,
        'hosting_plan_price_id' => $planPrice->id,
    ]);

    $domain = Domain::factory()->create([
        'subscription_id' => $subscription->id,
    ]);

    expect($subscription->linkedDomain)->not->toBeNull()
        ->and($subscription->linkedDomain->id)->toBe($domain->id);
});
