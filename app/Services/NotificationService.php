<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\FailedDomainRegistration;
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

    /**
     * Notify user of failed domain registration
     */
    public function notifyUserOfFailedRegistration(Order $order, array $results): void
    {
        Log::info('Notifying user of failed domain registration', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'user_id' => $order->user_id,
            'failed_count' => count($results['failed']),
        ]);

        $order->user->notify(new \App\Notifications\DomainRegistrationFailedUserNotification($order, $results));
    }

    /**
     * Notify admin that a domain registration has been abandoned
     */
    public function notifyAdminOfAbandonedRegistration(Order $order, FailedDomainRegistration $failedRegistration): void
    {
        Log::critical('Domain registration abandoned after all retries', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'failed_registration_id' => $failedRegistration->id,
            'domain' => $failedRegistration->domain_name,
            'retry_count' => $failedRegistration->retry_count,
        ]);

        // Send email to admin
        $adminEmail = config('mail.admin_email', config('mail.from.address'));

        if ($adminEmail) {
            Notification::route('mail', $adminEmail)
                ->notify(new \App\Notifications\DomainRegistrationAbandonedNotification($order, $failedRegistration, true));
        }
    }

    /**
     * Notify user that a domain registration has been abandoned
     */
    public function notifyUserOfAbandonedRegistration(Order $order, FailedDomainRegistration $failedRegistration): void
    {
        Log::info('Notifying user of abandoned domain registration', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'user_id' => $order->user_id,
            'failed_registration_id' => $failedRegistration->id,
            'domain' => $failedRegistration->domain_name,
        ]);

        $order->user->notify(new \App\Notifications\DomainRegistrationAbandonedNotification($order, $failedRegistration, false));
    }
}
