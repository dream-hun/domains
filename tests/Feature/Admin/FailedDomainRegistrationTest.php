<?php

declare(strict_types=1);

use App\Models\Contact;
use App\Models\Country;
use App\Models\Currency;
use App\Models\FailedDomainRegistration;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create base currency (required by admin layout)
    Currency::create([
        'code' => 'USD',
        'name' => 'US Dollar',
        'symbol' => '$',
        'exchange_rate' => 1.0,
        'is_base' => true,
    ]);

    // Create default user role first (required by User model)
    $defaultRole = Role::create(['id' => 2, 'title' => 'User']);

    // Create permissions
    $accessPermission = Permission::create(['title' => 'failed_registration_access']);
    $retryPermission = Permission::create(['title' => 'failed_registration_retry']);

    // Create admin role with permissions
    $this->adminRole = Role::create(['title' => 'Admin']);
    $this->adminRole->permissions()->attach([$accessPermission->id, $retryPermission->id]);

    // Create admin user
    $this->adminUser = User::factory()->create();
    $this->adminUser->roles()->attach($this->adminRole);

    // Create regular user without permissions
    $this->regularUser = User::factory()->create();

    // Create test data
    $this->country = Country::factory()->create(['iso_code' => 'US']);
    $this->contact = Contact::factory()->create([
        'user_id' => $this->adminUser->id,
        'country_code' => 'US',
    ]);

    $this->order = Order::factory()->create(['user_id' => $this->adminUser->id]);
    $this->orderItem = OrderItem::factory()->create(['order_id' => $this->order->id]);

    $this->failedRegistration = FailedDomainRegistration::create([
        'order_id' => $this->order->id,
        'order_item_id' => $this->orderItem->id,
        'domain_name' => 'example.com',
        'failure_reason' => 'Connection timeout',
        'retry_count' => 0,
        'max_retries' => 3,
        'status' => 'pending',
        'contact_ids' => [
            'registrant' => $this->contact->id,
            'admin' => $this->contact->id,
            'technical' => $this->contact->id,
            'billing' => $this->contact->id,
        ],
    ]);
});

it('allows admin with permission to view failed registrations index', function () {
    $response = $this->actingAs($this->adminUser)
        ->get(route('admin.failed-registrations.index'));

    $response->assertOk();
    $response->assertViewIs('admin.failed-registrations.index');
    $response->assertViewHas('failedRegistrations');
    $response->assertSee('example.com');
});

it('denies access to failed registrations index for user without permission', function () {
    $response = $this->actingAs($this->regularUser)
        ->get(route('admin.failed-registrations.index'));

    $response->assertForbidden();
});

it('denies access to failed registrations index for guests', function () {
    $response = $this->get(route('admin.failed-registrations.index'));

    $response->assertRedirect(route('login'));
});

it('allows admin to view failed registration details', function () {
    $response = $this->actingAs($this->adminUser)
        ->get(route('admin.failed-registrations.show', $this->failedRegistration));

    $response->assertOk();
    $response->assertViewIs('admin.failed-registrations.show');
    $response->assertViewHas('failedRegistration');
    $response->assertSee('example.com');
    $response->assertSee('Connection timeout');
});

it('allows admin to view manual registration form', function () {
    $response = $this->actingAs($this->adminUser)
        ->get(route('admin.failed-registrations.manual-register'));

    $response->assertOk();
    $response->assertViewIs('admin.failed-registrations.manual-register');
    $response->assertViewHas(['users', 'countries', 'contacts']);
});

it('denies access to manual registration form for user without permission', function () {
    $response = $this->actingAs($this->regularUser)
        ->get(route('admin.failed-registrations.manual-register'));

    $response->assertForbidden();
});

it('filters failed registrations by status', function () {
    // Create another failed registration with different status
    FailedDomainRegistration::create([
        'order_id' => $this->order->id,
        'order_item_id' => $this->orderItem->id,
        'domain_name' => 'abandoned.com',
        'failure_reason' => 'Max retries exceeded',
        'retry_count' => 3,
        'max_retries' => 3,
        'status' => 'abandoned',
        'contact_ids' => ['registrant' => $this->contact->id],
    ]);

    $response = $this->actingAs($this->adminUser)
        ->get(route('admin.failed-registrations.index', ['status' => 'abandoned']));

    $response->assertOk();
    $response->assertSee('abandoned.com');
});

it('validates required fields for manual registration', function () {
    $response = $this->actingAs($this->adminUser)
        ->post(route('admin.failed-registrations.manual-register.store'), []);

    $response->assertSessionHasErrors([
        'domain_name',
        'user_id',
        'years',
        'registrant_contact_id',
        'admin_contact_id',
        'technical_contact_id',
        'billing_contact_id',
    ]);
});

it('validates user_id exists for manual registration', function () {
    $response = $this->actingAs($this->adminUser)
        ->post(route('admin.failed-registrations.manual-register.store'), [
            'domain_name' => 'test.com',
            'user_id' => 99999, // Non-existent user
            'years' => 1,
            'registrant_contact_id' => $this->contact->id,
            'admin_contact_id' => $this->contact->id,
            'technical_contact_id' => $this->contact->id,
            'billing_contact_id' => $this->contact->id,
        ]);

    $response->assertSessionHasErrors(['user_id']);
});

it('validates contact IDs exist for manual registration', function () {
    $response = $this->actingAs($this->adminUser)
        ->post(route('admin.failed-registrations.manual-register.store'), [
            'domain_name' => 'test.com',
            'user_id' => $this->adminUser->id,
            'years' => 1,
            'registrant_contact_id' => 99999, // Non-existent contact
            'admin_contact_id' => $this->contact->id,
            'technical_contact_id' => $this->contact->id,
            'billing_contact_id' => $this->contact->id,
        ]);

    $response->assertSessionHasErrors(['registrant_contact_id']);
});

it('validates years range for manual registration', function () {
    $response = $this->actingAs($this->adminUser)
        ->post(route('admin.failed-registrations.manual-register.store'), [
            'domain_name' => 'test.com',
            'user_id' => $this->adminUser->id,
            'years' => 15, // Exceeds max of 10
            'registrant_contact_id' => $this->contact->id,
            'admin_contact_id' => $this->contact->id,
            'technical_contact_id' => $this->contact->id,
            'billing_contact_id' => $this->contact->id,
        ]);

    $response->assertSessionHasErrors(['years']);
});

it('prevents retry when registration cannot be retried', function () {
    // Mark registration as resolved
    $this->failedRegistration->update(['status' => 'resolved']);

    $response = $this->actingAs($this->adminUser)
        ->post(route('admin.failed-registrations.retry', $this->failedRegistration));

    $response->assertRedirect();
    $response->assertSessionHas('error');
});

it('prevents retry for user without permission', function () {
    $response = $this->actingAs($this->regularUser)
        ->post(route('admin.failed-registrations.retry', $this->failedRegistration));

    $response->assertForbidden();
});

it('shows failed registrations in paginated list', function () {
    // Create multiple failed registrations
    for ($i = 1; $i <= 25; $i++) {
        FailedDomainRegistration::create([
            'order_id' => $this->order->id,
            'order_item_id' => $this->orderItem->id,
            'domain_name' => "example{$i}.com",
            'failure_reason' => 'Test failure',
            'retry_count' => 0,
            'max_retries' => 3,
            'status' => 'pending',
            'contact_ids' => ['registrant' => $this->contact->id],
        ]);
    }

    $response = $this->actingAs($this->adminUser)
        ->get(route('admin.failed-registrations.index'));

    $response->assertOk();
    // Should see pagination since we have more than 20 items
    $response->assertViewHas('failedRegistrations');
});

it('displays correct status badges in index', function () {
    // Create registrations with different statuses
    $pending = FailedDomainRegistration::create([
        'order_id' => $this->order->id,
        'order_item_id' => $this->orderItem->id,
        'domain_name' => 'pending.com',
        'failure_reason' => 'Test',
        'status' => 'pending',
        'contact_ids' => ['registrant' => $this->contact->id],
    ]);

    $abandoned = FailedDomainRegistration::create([
        'order_id' => $this->order->id,
        'order_item_id' => $this->orderItem->id,
        'domain_name' => 'abandoned.com',
        'failure_reason' => 'Test',
        'status' => 'abandoned',
        'contact_ids' => ['registrant' => $this->contact->id],
    ]);

    $response = $this->actingAs($this->adminUser)
        ->get(route('admin.failed-registrations.index'));

    $response->assertOk();
    $response->assertSee('Pending');
    $response->assertSee('Abandoned');
});
