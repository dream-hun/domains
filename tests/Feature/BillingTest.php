<?php

declare(strict_types=1);

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Role;
use App\Models\User;

beforeEach(function (): void {
    // Create roles needed for tests
    Role::query()->create(['id' => 1, 'title' => 'Admin']);
    Role::query()->create(['id' => 2, 'title' => 'User']);

    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('user can view billing index page', function (): void {
    $response = $this->get(route('billing.index'));

    $response->assertSuccessful();
    $response->assertViewIs('admin.billing.index');
});

test('user can view their own orders on billing index', function (): void {
    $order = Order::factory()->paid()->create([
        'user_id' => $this->user->id,
    ]);

    $response = $this->get(route('billing.index'));

    $response->assertSuccessful();
    $response->assertSee($order->order_number);
});

test('user cannot view other users orders on billing index', function (): void {
    $otherUser = User::factory()->create();
    $otherOrder = Order::factory()->paid()->create([
        'user_id' => $otherUser->id,
    ]);

    $response = $this->get(route('billing.index'));

    $response->assertSuccessful();
    $response->assertDontSee($otherOrder->order_number);
});

test('admin can view all orders on billing index', function (): void {
    // Create an admin user with role_id 1
    $adminUser = User::factory()->create();
    $adminUser->roles()->attach(1);

    $order1 = Order::factory()->paid()->create(['user_id' => $this->user->id]);
    $order2 = Order::factory()->paid()->create(['user_id' => User::factory()->create()->id]);

    $response = $this->actingAs($adminUser)->get(route('billing.index'));

    $response->assertSuccessful();
    $response->assertSee($order1->order_number);
    $response->assertSee($order2->order_number);
});

test('user can view their order details', function (): void {
    $order = Order::factory()->paid()->create([
        'user_id' => $this->user->id,
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'domain_name' => 'example.com',
    ]);

    $response = $this->get(route('billing.show', $order));

    $response->assertSuccessful();
    $response->assertViewIs('admin.billing.show');
    $response->assertSee($order->order_number);
    $response->assertSee('example.com');
});

test('user cannot view other users order details', function (): void {
    $otherUser = User::factory()->create();
    $otherOrder = Order::factory()->paid()->create([
        'user_id' => $otherUser->id,
    ]);

    $response = $this->get(route('billing.show', $otherOrder));

    $response->assertForbidden();
});

test('admin can view any order details', function (): void {
    $adminUser = User::factory()->create();
    $adminUser->roles()->attach(1);

    $order = Order::factory()->paid()->create([
        'user_id' => User::factory()->create()->id,
    ]);

    $response = $this->actingAs($adminUser)->get(route('billing.show', $order));

    $response->assertSuccessful();
    $response->assertSee($order->order_number);
});

test('user can view their invoice', function (): void {
    $order = Order::factory()->paid()->create([
        'user_id' => $this->user->id,
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
    ]);

    $response = $this->get(route('billing.invoice', $order));

    $response->assertSuccessful();
    $response->assertViewIs('admin.billing.invoice');
    $response->assertSee($order->order_number);
    $response->assertSee('INVOICE');
});

test('user cannot view other users invoice', function (): void {
    $otherUser = User::factory()->create();
    $otherOrder = Order::factory()->paid()->create([
        'user_id' => $otherUser->id,
    ]);

    $response = $this->get(route('billing.invoice', $otherOrder));

    $response->assertForbidden();
});

test('user can download their invoice as pdf', function (): void {
    $order = Order::factory()->paid()->create([
        'user_id' => $this->user->id,
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
    ]);

    $response = $this->get(route('billing.invoice.download', $order));

    $response->assertSuccessful();
    $response->assertHeader('content-type', 'application/pdf');
    $response->assertDownload('invoice-'.$order->order_number.'.pdf');
});

test('user cannot download other users invoice', function (): void {
    $otherUser = User::factory()->create();
    $otherOrder = Order::factory()->paid()->create([
        'user_id' => $otherUser->id,
    ]);

    $response = $this->get(route('billing.invoice.download', $otherOrder));

    $response->assertForbidden();
});

test('user can view their invoice pdf in browser', function (): void {
    $order = Order::factory()->paid()->create([
        'user_id' => $this->user->id,
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
    ]);

    $response = $this->get(route('billing.invoice.view-pdf', $order));

    $response->assertSuccessful();
    $response->assertHeader('content-type', 'application/pdf');
});

test('billing index shows correct payment status badges', function (): void {
    $paidOrder = Order::factory()->paid()->create(['user_id' => $this->user->id]);
    $pendingOrder = Order::factory()->pending()->create(['user_id' => $this->user->id]);
    $failedOrder = Order::factory()->failed()->create(['user_id' => $this->user->id]);

    $response = $this->get(route('billing.index'));

    $response->assertSuccessful();
    $response->assertSee('Paid');
    $response->assertSee('Pending');
    $response->assertSee('Failed');
});

test('orders are displayed in descending order by creation date', function (): void {
    $oldOrder = Order::factory()->create([
        'user_id' => $this->user->id,
        'created_at' => now()->subDays(2),
    ]);

    $newOrder = Order::factory()->create([
        'user_id' => $this->user->id,
        'created_at' => now(),
    ]);

    $response = $this->get(route('billing.index'));

    $response->assertSuccessful();
    // Assert that new order appears before old order in the HTML
    expect($response->getContent())
        ->toContain($newOrder->order_number)
        ->toContain($oldOrder->order_number);
});
