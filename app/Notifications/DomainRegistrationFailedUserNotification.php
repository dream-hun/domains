<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class DomainRegistrationFailedUserNotification extends Notification
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

        $successfulCount = count($this->results['successful']);
        $failedCount = count($this->results['failed']);

        $message = (new MailMessage)
            ->subject('Your Domain Registration - Order '.$this->order->order_number)
            ->greeting('Hello '.$this->order->user->name.',')
            ->line('Thank you for your payment! Your order has been received.');

        if ($successfulCount > 0) {
            $message->line('**Successfully Registered Domains:** '.$successfulCount);
        }

        if ($failedCount > 0) {
            $message->line('**Domains Being Processed:** '.$failedDomains);
            $message->line('Some domains could not be registered immediately due to registrar processing. Don\'t worry - we\'re automatically retrying the registration and will notify you once complete.');
        }

        $message->line('**Order Number:** '.$this->order->order_number)
            ->line('**Order Total:** '.$this->order->currency.' '.number_format($this->order->total_amount, 2))
            ->line('You can check your order status at any time from your dashboard.')
            ->action('View Order', url('/orders/'.$this->order->order_number))
            ->line('If you have any questions, please don\'t hesitate to contact our support team.');

        return $message;
    }
}
