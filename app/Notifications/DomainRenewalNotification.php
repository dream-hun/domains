<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * @phpstan-type RenewalItem array{name: string, years: int}
 */
final class DomainRenewalNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<int, RenewalItem>  $renewalItems
     */
    public function __construct(
        private readonly Order $order,
        private readonly array $renewalItems
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $domainsSummary = collect($this->renewalItems)
            ->map(fn (array $item): string => sprintf(
                '%s (%d year%s)',
                $item['name'],
                $item['years'],
                $item['years'] === 1 ? '' : 's'
            ))
            ->implode(', ');

        return (new MailMessage)
            ->subject('Domain Renewal In Progress - Order '.$this->order->order_number)
            ->greeting('Hi '.$notifiable->name.',')
            ->line('We have started processing your domain renewal order '.$this->order->order_number.'.')
            ->line('Renewals: '.$domainsSummary)
            ->line('We will send another update as soon as the process completes.')
            ->action('View Order Status', route('billing.show', ['order' => $this->order->order_number]))
            ->line('Thank you for trusting us with your domains.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'domain_count' => count($this->renewalItems),
            'domains' => $this->renewalItems,
            'message' => 'Domain renewals are now processing.',
        ];
    }
}
