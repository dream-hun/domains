<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class PartialRegistrationFailureNotification extends Notification
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
        $successfulDomains = collect($this->results['successful'])
            ->pluck('domain')
            ->implode(', ');

        $failedDomains = collect($this->results['failed'])
            ->pluck('domain')
            ->implode(', ');

        return (new MailMessage)
            ->level('warning')
            ->subject('WARNING: Partial Domain Registration Failure - Order '.$this->order->order_number)
            ->greeting('Partial Registration Failure')
            ->line('Payment was successful but some domain registrations failed for order '.$this->order->order_number)
            ->line('**Successful Domains:** '.$successfulDomains)
            ->line('**Failed Domains:** '.$failedDomains)
            ->line('**Order Total:** '.$this->order->currency.' '.$this->order->total_amount)
            ->line('**Customer:** '.$this->order->user->name.' ('.$this->order->user->email.')')
            ->line('Please investigate the failed registrations and take appropriate action.')
            ->action('View Order', url('/admin/orders/'.$this->order->order_number));
    }
}
