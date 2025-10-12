<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Domain;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class DomainImportedNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        private readonly Domain $domain,
        private readonly int $totalImported = 1
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
        $message = $this->totalImported > 1
            ? "{$this->totalImported} domains have been imported successfully."
            : "Domain '{$this->domain->name}' has been imported successfully.";

        return (new MailMessage)
            ->subject('Domain Import Notification')
            ->greeting('Hello!')
            ->line($message)
            ->line("Domain: {$this->domain->name}")
            ->line("Registered: {$this->domain->registeredAt()}")
            ->line("Expires: {$this->domain->expiresAt()}")
            ->line('Status: '.ucfirst($this->domain->status))
            ->action('View Domains', route('admin.domains.index'))
            ->line('Thank you for using our domain management system!');
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
            'status' => $this->domain->status,
            'total_imported' => $this->totalImported,
            'import_type' => 'bulk_import',
        ];
    }
}
