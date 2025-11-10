<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Events\WebhookReceived;

final class HandleStripeWebhook implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the webhook event.
     */
    public function handle(WebhookReceived $event): void
    {
        switch ($event->payload['type']) {
            case 'checkout.session.completed':
                $this->handleCheckoutSessionCompleted($event->payload['data']['object']);
                break;
            case 'payment_intent.succeeded':
                $this->handlePaymentIntentSucceeded($event->payload['data']['object']);
                break;
            case 'payment_intent.payment_failed':
                $this->handlePaymentIntentFailed($event->payload['data']['object']);
                break;
        }
    }

    /**
     * Handle checkout session completed webhook
     */
    private function handleCheckoutSessionCompleted(array $session): void
    {
        $order = Order::query()->where('stripe_session_id', $session['id'])->first();

        if ($order && $order->payment_status === 'pending') {
            $order->update([
                'payment_status' => 'paid',
                'status' => 'processing',
                'stripe_payment_intent_id' => $session['payment_intent'],
                'processed_at' => now(),
            ]);

            Log::info('Order payment confirmed via webhook', [
                'order_number' => $order->order_number,
                'stripe_session_id' => $session['id'],
            ]);
        }
    }

    /**
     * Handle payment intent succeeded webhook
     */
    private function handlePaymentIntentSucceeded(array $paymentIntent): void
    {
        $order = Order::query()->where('stripe_payment_intent_id', $paymentIntent['id'])->first();

        if ($order && $order->payment_status === 'pending') {
            $order->update([
                'payment_status' => 'paid',
                'status' => 'processing',
                'processed_at' => now(),
            ]);

            Log::info('Order payment confirmed via payment intent webhook', [
                'order_number' => $order->order_number,
                'payment_intent_id' => $paymentIntent['id'],
            ]);
        }
    }

    /**
     * Handle payment intent failed webhook
     */
    private function handlePaymentIntentFailed(array $paymentIntent): void
    {
        $order = Order::query()->where('stripe_payment_intent_id', $paymentIntent['id'])->first();

        if ($order) {
            $order->update([
                'payment_status' => 'failed',
                'status' => 'cancelled',
            ]);

            Log::info('Order payment failed via webhook', [
                'order_number' => $order->order_number,
                'payment_intent_id' => $paymentIntent['id'],
            ]);
        }
    }
}
