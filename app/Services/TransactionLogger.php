<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;

final class TransactionLogger
{
    /**
     * Log successful transaction
     */
    public function logSuccess(
        Order $order,
        string $method,
        string $transactionId,
        float $amount
    ): Transaction {
        Log::info('Transaction successful', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'payment_method' => $method,
            'transaction_id' => $transactionId,
            'amount' => $amount,
            'currency' => $order->currency,
        ]);

        return Transaction::create([
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'payment_method' => $method,
            'transaction_id' => $transactionId,
            'amount' => $amount,
            'currency' => $order->currency,
            'status' => 'completed',
            'processed_at' => now(),
        ]);
    }

    /**
     * Log failed transaction
     */
    public function logFailure(
        Order $order,
        string $method,
        string $error,
        ?string $details = null
    ): Transaction {
        Log::error('Transaction failed', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'payment_method' => $method,
            'error' => $error,
            'details' => $details,
            'amount' => $order->total_amount,
            'currency' => $order->currency,
        ]);

        return Transaction::create([
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'payment_method' => $method,
            'amount' => $order->total_amount,
            'currency' => $order->currency,
            'status' => 'failed',
            'error_message' => $error,
            'error_details' => $details,
            'processed_at' => now(),
        ]);
    }
}
