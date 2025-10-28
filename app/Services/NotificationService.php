<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

final class NotificationService
{
    /**
     * Notify admin of failed domain registration
     */
    public function notifyAdminOfFailedRegistration(Order $order, array $results): void
    {
        Log::critical('All domain registrations failed for order', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'failed_domains' => $results['failed'],
        ]);

        // Send email to admin
        $adminEmail = config('mail.admin_email', config('mail.from.address'));

        if ($adminEmail) {
            Notification::route('mail', $adminEmail)
                ->notify(new \App\Notifications\DomainRegistrationFailedNotification($order, $results));
        }
    }

    /**
     * Notify admin of partial domain registration failure
     */
    public function notifyAdminOfPartialFailure(Order $order, array $results): void
    {
        Log::warning('Partial domain registration failure for order', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'successful' => $results['successful'],
            'failed' => $results['failed'],
        ]);

        // Send email to admin
        $adminEmail = config('mail.admin_email', config('mail.from.address'));

        if ($adminEmail) {
            Notification::route('mail', $adminEmail)
                ->notify(new \App\Notifications\PartialRegistrationFailureNotification($order, $results));
        }
    }

    /**
     * Notify admin of critical failure (payment succeeded but registration failed)
     */
    public function notifyAdminOfCriticalFailure(Order $order, Exception $exception): void
    {
        Log::critical('Critical failure: Payment succeeded but domain registration failed', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Send email to admin
        $adminEmail = config('mail.admin_email', config('mail.from.address'));

        if ($adminEmail) {
            Notification::route('mail', $adminEmail)
                ->notify(new \App\Notifications\CriticalOrderFailureNotification($order, $exception));
        }
    }
}
