<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Domain;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class DomainAutoRenewalFailedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Domain $domain,
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
        $domainName = $this->domain->name;
        $expiryDate = $this->domain->expires_at->format('F d, Y');

        return (new MailMessage)
            ->error()
            ->subject('Automatic Domain Renewal Failed')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('We were unable to automatically renew your domain.')
            ->line('**Domain:** '.$domainName)
            ->line('**Expires on:** '.$expiryDate)
            ->line('**Reason:** '.$this->failureReason)
            ->line('To avoid losing your domain, please contact our support team to update your payment method or renew your domain.')
            ->action('Contact Support', route('dashboard'))
            ->line('Our team will assist you with renewing your domain.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'domain_auto_renewal_failed',
            'domain_id' => $this->domain->id,
            'domain_name' => $this->domain->name,
            'expires_at' => $this->domain->expires_at->toIso8601String(),
            'failure_reason' => $this->failureReason,
            'message' => 'Automatic renewal failed for your domain',
        ];
    }
}
