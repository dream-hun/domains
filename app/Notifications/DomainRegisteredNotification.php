<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Domain;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class DomainRegisteredNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        private readonly Domain $domain,
        private readonly int $registrationYears = 1
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Domain Registration Successful')
            ->greeting('Congratulations!')
            ->line(sprintf("Your domain '%s' has been successfully registered.", $this->domain->name))
            ->line('Domain: '.$this->domain->name)
            ->line(sprintf('Registration Period: %d year(s)', $this->registrationYears))
            ->line('Registered: '.$this->domain->registeredAt())
            ->line('Expires: '.$this->domain->expiresAt())
            ->line('Status: '.ucfirst($this->domain->status))
            ->action('Manage Domain', route('admin.domains.index'))
            ->line('Your domain is now active and ready to use!')
            ->line('Thank you for choosing our domain registration service!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'domain_id' => $this->domain->id,
            'domain_name' => $this->domain->name,
            'domain_uuid' => $this->domain->uuid,
            'registered_at' => $this->domain->registered_at?->toISOString(),
            'expires_at' => $this->domain->expires_at?->toISOString(),
            'registration_years' => $this->registrationYears,
            'status' => $this->domain->status,
            'registration_type' => 'new_registration',
        ];
    }
}
