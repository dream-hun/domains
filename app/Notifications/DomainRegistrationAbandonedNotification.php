<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\FailedDomainRegistration;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class DomainRegistrationAbandonedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Order $order, private readonly FailedDomainRegistration $failedRegistration, private readonly bool $isForAdmin = false) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        if ($this->isForAdmin) {
            return $this->adminMail();
        }

        return $this->userMail();
    }

    private function adminMail(): MailMessage
    {
        return (new MailMessage)
            ->error()
            ->subject('URGENT: Domain Registration Abandoned - Order '.$this->order->order_number)
            ->greeting('Domain Registration Abandoned')
            ->line('All retry attempts exhausted for domain registration.')
            ->line('**Domain:** '.$this->failedRegistration->domain_name)
            ->line('**Order Number:** '.$this->order->order_number)
            ->line('**Customer:** '.$this->order->user->name.' ('.$this->order->user->email.')')
            ->line('**Retry Attempts:** '.$this->failedRegistration->retry_count.'/'.$this->failedRegistration->max_retries)
            ->line('**Last Error:** '.$this->failedRegistration->failure_reason)
            ->line('**Order Total:** '.$this->order->currency.' '.$this->order->total_amount)
            ->line('Action Required: Please manually register the domain or process a refund.')
            ->action('View Order', url('/admin/orders/'.$this->order->order_number));
    }

    private function userMail(): MailMessage
    {
        return (new MailMessage)
            ->subject('Important: Domain Registration Issue - Order '.$this->order->order_number)
            ->greeting('Hello '.$this->order->user->name.',')
            ->line("We apologize, but we've encountered persistent issues registering your domain.")
            ->line('**Domain:** '.$this->failedRegistration->domain_name)
            ->line('**Order Number:** '.$this->order->order_number)
            ->line('Our support team has been notified and will contact you shortly to resolve this issue. We may need to manually complete the registration or issue a refund.')
            ->line('We sincerely apologize for the inconvenience.')
            ->action('View Order', url('/orders/'.$this->order->order_number))
            ->line('If you have any immediate concerns, please contact our support team.');
    }
}
