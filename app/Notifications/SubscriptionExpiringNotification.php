<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class SubscriptionExpiringNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Subscription $subscription,
        public int $daysUntilExpiry
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
        $planName = $this->subscription->plan->name;
        $domain = $this->subscription->domain ?? 'N/A';
        $expiryDate = $this->subscription->expires_at->format('F d, Y');

        $subject = $this->daysUntilExpiry === 0
            ? 'Your Subscription Expires Today'
            : sprintf('Your Subscription Expires in %s Day', $this->daysUntilExpiry).($this->daysUntilExpiry > 1 ? 's' : '');

        $urgencyMessage = match (true) {
            $this->daysUntilExpiry === 0 => 'Your subscription expires today! Renew now to avoid service interruption.',
            $this->daysUntilExpiry <= 3 => 'Your subscription is expiring very soon. Please renew as soon as possible.',
            default => 'Your subscription will expire soon. We recommend renewing to ensure uninterrupted service.',
        };

        return (new MailMessage)
            ->subject($subject)
            ->greeting('Hello '.$notifiable->name.',')
            ->line($urgencyMessage)
            ->line('**Plan:** '.$planName)
            ->line('**Domain:** '.$domain)
            ->line('**Expires on:** '.$expiryDate)
            ->action('View Details', route('dashboard'))
            ->line('Please contact our support team to renew your subscription.')
            ->line("If you have any questions or need assistance, please don't hesitate to reach out.");
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'subscription_expiring',
            'subscription_id' => $this->subscription->id,
            'subscription_uuid' => $this->subscription->uuid,
            'plan_name' => $this->subscription->plan->name,
            'domain' => $this->subscription->domain,
            'days_until_expiry' => $this->daysUntilExpiry,
            'expires_at' => $this->subscription->expires_at->toIso8601String(),
            'message' => $this->daysUntilExpiry === 0
                ? 'Your subscription expires today'
                : sprintf('Your subscription expires in %s day', $this->daysUntilExpiry).($this->daysUntilExpiry > 1 ? 's' : ''),
        ];
    }
}
