<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class RenewalInvoiceNotification extends Notification implements ShouldQueue
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
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Renewal Invoice - '.$this->order->order_number)
            ->greeting('Hello '.$notifiable->name.',')
            ->line('A renewal invoice has been generated for your upcoming renewal.')
            ->line('**Invoice Number:** '.$this->order->order_number)
            ->line('**Total Amount:** '.$this->order->currency.' '.number_format((float) $this->order->total_amount, 2));

        foreach ($this->order->orderItems as $item) {
            $message->line('- '.$item->domain_name.' ('.$item->currency.' '.number_format((float) $item->total_amount, 2).')');
        }

        $message->action('View Invoice', route('orders.show', $this->order->order_number))
            ->line('Please ensure your payment method is up to date.')
            ->line('If you have any questions, please contact our support team.');

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
            'type' => 'renewal_invoice',
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'total_amount' => $this->order->total_amount,
            'currency' => $this->order->currency,
            'message' => 'Renewal invoice '.$this->order->order_number.' has been generated.',
        ];
    }
}
