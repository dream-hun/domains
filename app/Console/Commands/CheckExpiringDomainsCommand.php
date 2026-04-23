<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Domain;
use App\Notifications\DomainExpiringNotification;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;

final class CheckExpiringDomainsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'domains:check-expiring
                            {--days=7 : Number of days before expiry to send notification}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for expiring domains and send reminder notifications';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $now = Date::now();
        $endDate = $now->copy()->addDays($days);

        $this->info(sprintf('Checking for domains expiring within %d days...', $days));

        $expiringDomains = Domain::query()
            ->withoutGlobalScopes()
            ->where('status', 'active')
            ->where('auto_renew', false)
            ->whereBetween('expires_at', [$now, $endDate])
            ->with('owner')
            ->get();

        if ($expiringDomains->isEmpty()) {
            $this->info('No expiring domains found.');

            return self::SUCCESS;
        }

        $notificationsSent = 0;

        foreach ($expiringDomains as $domain) {
            $owner = $domain->owner;

            if (! $owner) {
                Log::warning('Domain has no associated owner', [
                    'domain_id' => $domain->id,
                ]);

                continue;
            }

            $daysUntilExpiry = (int) $now->diffInDays($domain->expires_at);

            if (! in_array($daysUntilExpiry, [7, 3, 1, 0], true)) {
                continue;
            }

            $lastNotification = $owner->notifications()
                ->where('type', DomainExpiringNotification::class)
                ->where('data->domain_id', $domain->id)
                ->where('data->days_until_expiry', $daysUntilExpiry)
                ->where('created_at', '>=', $now->copy()->subDay())
                ->exists();

            if ($lastNotification) {
                $this->line(sprintf('Skipping domain %s - notification already sent today', $domain->name));

                continue;
            }

            try {
                $owner->notify(new DomainExpiringNotification($domain, $daysUntilExpiry));
                $notificationsSent++;

                $this->line(sprintf('Sent expiring notification for domain %s (%s days)', $domain->name, $daysUntilExpiry));

                Log::info('Domain expiring notification sent', [
                    'domain_id' => $domain->id,
                    'user_id' => $owner->id,
                    'days_until_expiry' => $daysUntilExpiry,
                ]);
            } catch (Exception $exception) {
                $this->error(sprintf('Failed to send notification for domain %s: %s', $domain->name, $exception->getMessage()));

                Log::error('Failed to send domain expiring notification', [
                    'domain_id' => $domain->id,
                    'user_id' => $owner->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        $this->info(sprintf('Processed %s expiring domains, sent %d notifications.', $expiringDomains->count(), $notificationsSent));

        return self::SUCCESS;
    }
}
