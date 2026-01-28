<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\SubscriptionInvoiceGenerationService;
use Illuminate\Console\Command;

final class GenerateSubscriptionRenewalInvoicesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:generate-renewal-invoices
                            {--days=7 : Number of days before renewal to generate invoice}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate renewal invoices for subscriptions due for renewal';

    /**
     * Execute the console command.
     */
    public function handle(SubscriptionInvoiceGenerationService $service): int
    {
        $days = (int) $this->option('days');

        $this->info(sprintf('Generating renewal invoices for subscriptions renewing within %d days...', $days));

        $results = $service->generateRenewalInvoices($days);

        $this->info(sprintf('Generated %d renewal invoices.', $results['generated']));

        if (! empty($results['failed'])) {
            $this->warn(sprintf('Failed to generate %d invoices:', count($results['failed'])));

            foreach ($results['failed'] as $failure) {
                $this->error(sprintf('  Subscription ID %d: %s', $failure['subscription_id'], $failure['error']));
            }

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
