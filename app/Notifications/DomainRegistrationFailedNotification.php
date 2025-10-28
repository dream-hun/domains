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
        $failedDomains = collect($this->results['failed'])
            ->pluck('domain')
            ->implode(', ');

        return (new MailMessage)
            ->error()
            ->subject('URGENT: Domain Registration Failed - Order '.$this->order->order_number)
            ->greeting('Domain Registration Failure')
            ->line('Payment was successful but ALL domain registrations failed for order '.$this->order->order_number)
            ->line('**Failed Domains:** '.$failedDomains)
            ->line('**Order Total:** '.$this->order->currency.' '.$this->order->total_amount)
            ->line('**Customer:** '.$this->order->user->name.' ('.$this->order->user->email.')')
            ->line('Please investigate and manually register these domains or process a refund.')
            ->action('View Order', url('/admin/orders/'.$this->order->order_number));
    }
}
