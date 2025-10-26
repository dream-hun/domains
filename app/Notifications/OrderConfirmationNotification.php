<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class OrderConfirmationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Order $order
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Order Confirmation - '.$this->order->order_number)
            ->greeting('Thank you for your order!')
            ->line('Your order has been successfully processed.')
            ->line('**Order Number:** '.$this->order->order_number)
            ->line('**Total Amount:** '.$this->order->currency.' '.number_format($this->order->total_amount, 2));

        // Add domain items
        $message->line('**Domains Purchased:**');
        foreach ($this->order->orderItems as $item) {
            $message->line('- '.$item->domain_name.' ('.$item->years.' '.($item->years > 1 ? 'years' : 'year').')');
        }

        $message->action('View Order Details', route('orders.show', $this->order->order_number))
            ->line('Your domains are being registered and will be available in your account shortly.')
            ->line('Thank you for choosing our service!');

        return $message;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'total_amount' => $this->order->total_amount,
            'currency' => $this->order->currency,
        ];
    }
}
