<?php

declare(strict_types=1);

use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderConfirmationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('notification uses mail channel', function (): void {
    $order = Order::factory()->create();

    $notification = new OrderConfirmationNotification($order);
    $channels = $notification->via(new stdClass);

    expect($channels)->toBe(['mail']);
});

test('toMail returns mail message without throwing', function (): void {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create();

    $notification = new OrderConfirmationNotification($order);
    $mail = $notification->toMail($user);

    expect($mail->subject)->toBe('Order Confirmation - '.$order->order_number);
});

test('toArray returns correct structure', function (): void {
    $order = Order::factory()->create();

    $notification = new OrderConfirmationNotification($order);
    $array = $notification->toArray(new stdClass);

    expect($array)
        ->toHaveKey('order_id', $order->id)
        ->toHaveKey('order_number', $order->order_number)
        ->toHaveKey('total_amount', $order->total_amount)
        ->toHaveKey('currency', $order->currency);
});
