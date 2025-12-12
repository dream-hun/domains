<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * @phpstan-ignore-next-line
 * This listener is not currently registered in EventServiceProvider.
 * The application uses StripeWebhookController instead.
 * Consider removing this file if it's not needed.
 */
final class HandleStripeWebhook implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the webhook event.
     *
     * @param  array{payload: array{type: string, data: array{object: array}}}  $event
     */
    public function handle(array $event): void
    {
        $payload = $event['payload'] ?? [];
        $eventType = $payload['type'] ?? '';

        switch ($eventType) {
            case 'checkout.session.completed':
                $this->handleCheckoutSessionCompleted($payload['data']['object'] ?? []);
                break;
            case 'payment_intent.succeeded':
                $this->handlePaymentIntentSucceeded($payload['data']['object'] ?? []);
                break;
            case 'payment_intent.payment_failed':
                $this->handlePaymentIntentFailed($payload['data']['object'] ?? []);
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
