<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Domain;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class DomainExpiringNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Domain $domain,
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
        $domainName = $this->domain->name;
        $expiryDate = $this->domain->expires_at->format('F d, Y');

        $subject = $this->daysUntilExpiry === 0
            ? 'Your Domain Expires Today'
            : sprintf('Your Domain Expires in %s Day', $this->daysUntilExpiry).($this->daysUntilExpiry > 1 ? 's' : '');

        $urgencyMessage = match (true) {
            $this->daysUntilExpiry === 0 => 'Your domain expires today! Renew now to avoid losing it.',
            $this->daysUntilExpiry <= 3 => 'Your domain is expiring very soon. Please renew as soon as possible.',
            default => 'Your domain will expire soon. We recommend renewing to keep ownership.',
        };

        return (new MailMessage)
            ->subject($subject)
            ->greeting('Hello '.$notifiable->name.',')
            ->line($urgencyMessage)
            ->line('**Domain:** '.$domainName)
            ->line('**Expires on:** '.$expiryDate)
            ->action('View Details', route('dashboard'))
            ->line('Please contact our support team to renew your domain.')
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
            'type' => 'domain_expiring',
            'domain_id' => $this->domain->id,
            'domain_name' => $this->domain->name,
            'days_until_expiry' => $this->daysUntilExpiry,
            'expires_at' => $this->domain->expires_at->toIso8601String(),
            'message' => $this->daysUntilExpiry === 0
                ? 'Your domain expires today'
                : sprintf('Your domain expires in %s day', $this->daysUntilExpiry).($this->daysUntilExpiry > 1 ? 's' : ''),
        ];
    }
}
