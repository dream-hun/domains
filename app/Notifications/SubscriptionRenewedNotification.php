<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Order;
use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class SubscriptionRenewedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @param  array<int, Subscription>  $subscriptions
     */
    public function __construct(
        public Order $order,
        public array $subscriptions
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
        $subscriptionCount = count($this->subscriptions);

        $message = (new MailMessage)
            ->subject('Subscription'.($subscriptionCount > 1 ? 's' : '').' Renewed Successfully')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Your hosting subscription'.($subscriptionCount > 1 ? 's have' : ' has').' been renewed successfully!');

        foreach ($this->subscriptions as $subscription) {
            $planName = $subscription->plan?->name ?? 'Hosting Plan';
            $domain = $subscription->domain ?? 'N/A';
            $expiryDate = $subscription->expires_at?->format('F d, Y') ?? 'N/A';

            $message->line(sprintf('**%s** (Domain: %s) - New expiry: %s', $planName, $domain, $expiryDate));
        }

        $message->line('Order Number: **'.$this->order->order_number.'**')
            ->line('Thank you for your continued business!')
            ->action('View Dashboard', route('dashboard'))
            ->line("If you have any questions, please don't hesitate to contact our support team.");

        return $message;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $subscriptionCount = count($this->subscriptions);

        return [
            'type' => 'subscription_renewed',
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'subscription_count' => $subscriptionCount,
            'subscriptions' => array_map(
                fn (Subscription $sub): array => [
                    'id' => $sub->id,
                    'uuid' => $sub->uuid,
                    'plan_name' => $sub->plan?->name,
                    'domain' => $sub->domain,
                    'expires_at' => $sub->expires_at?->toIso8601String(),
                ],
                $this->subscriptions
            ),
            'message' => $subscriptionCount > 1
                ? $subscriptionCount.' subscriptions renewed successfully'
                : 'Subscription renewed successfully',
        ];
    }
}
