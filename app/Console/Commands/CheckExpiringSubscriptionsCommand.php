<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Notifications\SubscriptionExpiringNotification;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;

final class CheckExpiringSubscriptionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:check-expiring
                            {--days=7 : Number of days before expiry to send notification}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for expiring subscriptions and send reminder notifications';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $now = Date::now();
        $endDate = $now->copy()->addDays($days);

        $this->info("Checking for subscriptions expiring within {$days} days...");

        // Find active subscriptions expiring within the specified days without auto-renewal
        $expiringSubscriptions = Subscription::query()
            ->where('status', 'active')
            ->where('auto_renew', false)
            ->whereBetween('expires_at', [$now, $endDate])
            ->with(['user', 'plan'])
            ->get();

        if ($expiringSubscriptions->isEmpty()) {
            $this->info('No expiring subscriptions found.');

            return self::SUCCESS;
        }

        $notificationsSent = 0;

        foreach ($expiringSubscriptions as $subscription) {
            $user = $subscription->user;

            if (! $user) {
                Log::warning('Subscription has no associated user', [
                    'subscription_id' => $subscription->id,
                ]);

                continue;
            }

            $daysUntilExpiry = (int) $now->diffInDays($subscription->expires_at, false);

            // Only send notifications for certain milestones: 7, 3, 1, 0 days
            if (! in_array($daysUntilExpiry, [7, 3, 1, 0], true)) {
                continue;
            }

            // Check if we've already sent a notification for this milestone
            // by checking the last notification sent to this user for this subscription
            $lastNotification = $user->notifications()
                ->where('type', SubscriptionExpiringNotification::class)
                ->where('data->subscription_id', $subscription->id)
                ->where('data->days_until_expiry', $daysUntilExpiry)
                ->where('created_at', '>=', $now->copy()->subDay())
                ->exists();

            if ($lastNotification) {
                $this->line("Skipping subscription {$subscription->uuid} - notification already sent today");

                continue;
            }

            try {
                $user->notify(new SubscriptionExpiringNotification($subscription, $daysUntilExpiry));
                $notificationsSent++;

                $this->line("Sent expiring notification for subscription {$subscription->uuid} ({$daysUntilExpiry} days)");

                Log::info('Subscription expiring notification sent', [
                    'subscription_id' => $subscription->id,
                    'user_id' => $user->id,
                    'days_until_expiry' => $daysUntilExpiry,
                ]);
            } catch (Exception $exception) {
                $this->error("Failed to send notification for subscription {$subscription->uuid}: {$exception->getMessage()}");

                Log::error('Failed to send subscription expiring notification', [
                    'subscription_id' => $subscription->id,
                    'user_id' => $user->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        $this->info("Processed {$expiringSubscriptions->count()} expiring subscriptions, sent {$notificationsSent} notifications.");

        return self::SUCCESS;
    }
}
