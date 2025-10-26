<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Order;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class CriticalOrderFailureNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Order $order,
        private readonly Exception $exception
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $domains = $this->order->orderItems->pluck('domain_name')->implode(', ');

        return (new MailMessage)
            ->error()
            ->subject('CRITICAL: Payment Succeeded but Registration Failed - Order '.$this->order->order_number)
            ->greeting('Critical Order Failure')
            ->line('**URGENT:** Payment was processed successfully but domain registration encountered a critical error.')
            ->line('**Order Number:** '.$this->order->order_number)
            ->line('**Domains:** '.$domains)
            ->line('**Order Total:** '.$this->order->currency.' '.$this->order->total_amount)
            ->line('**Customer:** '.$this->order->user->name.' ('.$this->order->user->email.')')
            ->line('**Error:** '.$this->exception->getMessage())
            ->line('**Action Required:** Manually register domains or process refund immediately.')
            ->action('View Order', url('/admin/orders/'.$this->order->order_number));
    }
}
