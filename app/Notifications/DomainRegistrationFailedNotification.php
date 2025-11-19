<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class DomainRegistrationFailedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Order $order,
        private readonly array $results
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $failedDomainsDetails = collect($this->results['failed'])
            ->map(fn ($failed): string => $failed['domain'].': '.$failed['message'])
            ->implode("\n");

        $failedDomainsList = collect($this->results['failed'])
            ->pluck('domain')
            ->implode(', ');

        $successfulCount = count($this->results['successful']);

        $message = (new MailMessage)
            ->error()
            ->subject('Domain Registration Issues - Order '.$this->order->order_number)
            ->greeting('Domain Registration Alert')
            ->line('Payment was successful, but some domain registrations encountered issues for order '.$this->order->order_number);

        if ($successfulCount > 0) {
            $message->line('**Successful Registrations:** '.$successfulCount);
        }

        $message->line('**Failed Domains:** '.$failedDomainsList)
            ->line('**Failed Domains Details:**')
            ->line($failedDomainsDetails)
            ->line('**Order Total:** '.$this->order->currency.' '.$this->order->total_amount)
            ->line('**Customer:** '.$this->order->user->name.' ('.$this->order->user->email.')')
            ->line('**Status:** Automatic retry has been scheduled.')
            ->line('The system will automatically retry registration every 1 hour (up to 3 attempts).')
            ->line('If all retries fail, you will receive another notification to take manual action.')
            ->action('View Order', url('/admin/orders/'.$this->order->order_number));

        return $message;
    }
}
