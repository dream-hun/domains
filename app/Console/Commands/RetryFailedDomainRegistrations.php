<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\RetryDomainRegistrationJob;
use App\Models\FailedDomainRegistration;
use Illuminate\Console\Command;

final class RetryFailedDomainRegistrations extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'domain:retry-failed 
                            {--order= : Filter by specific Order ID}
                            {--all : Retry all pending/retrying failed registrations}';

    /**
     * The console command description.
     */
    protected $description = 'Manually retry failed domain registrations';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $orderId = $this->option('order');
        $all = $this->option('all');

        if (! $orderId && ! $all) {
            $this->error('Please specify either --order=<ID> or --all');

            return self::FAILURE;
        }

        $query = FailedDomainRegistration::query()
            ->with(['order', 'orderItem'])
            ->whereIn('status', ['pending', 'retrying']);

        if ($orderId) {
            $query->where('order_id', $orderId);
        }

        $failedRegistrations = $query->get();

        if ($failedRegistrations->isEmpty()) {
            $this->info('No failed registrations found to retry.');

            return self::SUCCESS;
        }

        $this->info("Found {$failedRegistrations->count()} failed registration(s) to retry.");

        $this->table(
            ['ID', 'Order', 'Domain', 'Retry Count', 'Status', 'Last Error'],
            $failedRegistrations->map(function ($registration) {
                return [
                    $registration->id,
                    $registration->order->order_number,
                    $registration->domain_name,
                    $registration->retry_count.'/'.$registration->max_retries,
                    $registration->status,
                    mb_substr($registration->failure_reason, 0, 50).'...',
                ];
            })->toArray()
        );

        if (! $this->confirm('Do you want to dispatch retry jobs for these registrations?', true)) {
            $this->info('Operation cancelled.');

            return self::SUCCESS;
        }

        $dispatched = 0;
        $skipped = 0;

        foreach ($failedRegistrations as $failedRegistration) {
            if (! $failedRegistration->canRetry()) {
                $this->warn("Skipping {$failedRegistration->domain_name} - cannot retry (retry count: {$failedRegistration->retry_count}/{$failedRegistration->max_retries})");
                $skipped++;

                continue;
            }

            RetryDomainRegistrationJob::dispatch($failedRegistration);
            $dispatched++;
            $this->info("Dispatched retry job for {$failedRegistration->domain_name}");
        }

        $this->newLine();
        $this->info('Summary:');
        $this->info("  Dispatched: {$dispatched}");
        $this->info("  Skipped: {$skipped}");
        $this->newLine();
        $this->info('Retry jobs have been dispatched to the queue.');

        return self::SUCCESS;
    }
}
