<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class SubscriptionAutoRenewalFailedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Subscription $subscription,
        public string $failureReason
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $planName = $this->subscription->plan?->name ?? 'Hosting Plan';
        $domain = $this->subscription->domain ?? 'N/A';
        $expiryDate = $this->subscription->expires_at?->format('F d, Y') ?? 'Unknown';

        return (new MailMessage)
            ->error()
            ->subject('Automatic Subscription Renewal Failed')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('We were unable to automatically renew your hosting subscription.')
            ->line("**Plan:** {$planName}")
            ->line("**Domain:** {$domain}")
            ->line("**Expires on:** {$expiryDate}")
            ->line("**Reason:** {$this->failureReason}")
            ->line('To avoid service interruption, please contact our support team to update your payment method or renew your subscription.')
            ->action('Contact Support', route('dashboard'))
            ->line('Our team will assist you with renewing your subscription.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'subscription_auto_renewal_failed',
            'subscription_id' => $this->subscription->id,
            'subscription_uuid' => $this->subscription->uuid,
            'plan_name' => $this->subscription->plan?->name,
            'domain' => $this->subscription->domain,
            'expires_at' => $this->subscription->expires_at?->toIso8601String(),
            'failure_reason' => $this->failureReason,
            'message' => 'Automatic renewal failed for your subscription',
        ];
    }
}
