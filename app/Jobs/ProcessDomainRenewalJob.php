<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\RenewDomainAction;
use App\Models\Domain;
use App\Models\Order;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

final class ProcessDomainRenewalJob implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Order $order
    ) {}

    /**
     * Execute the job.
     */
    public function handle(RenewDomainAction $renewAction): void
    {
        try {
            Log::info('Processing domain renewals for order', [
                'order_id' => $this->order->id,
                'order_number' => $this->order->order_number,
            ]);

            $allSuccessful = true;
            $failedDomains = [];

            // Process each item in the order
            foreach ($this->order->items as $item) {
                // Check if this is a renewal item
                if (! isset($item['attributes']['type']) || $item['attributes']['type'] !== 'renewal') {
                    Log::warning('Skipping non-renewal item', [
                        'item_id' => $item['id'],
                        'item_name' => $item['name'] ?? 'unknown',
                    ]);

                    continue;
                }

                $domainId = $item['id'];
                $years = $item['attributes']['years'] ?? $item['quantity'];

                // Find the domain (bypass global scopes since we're in a job context)
                $domain = Domain::withoutGlobalScopes()->find($domainId);

                if (! $domain) {
                    Log::error('Domain not found for renewal', [
                        'domain_id' => $domainId,
                        'order_id' => $this->order->id,
                    ]);
                    $allSuccessful = false;
                    $failedDomains[] = $item['name'] ?? "Domain ID: {$domainId}";

                    continue;
                }

                Log::info('Processing renewal for domain', [
                    'domain_id' => $domain->id,
                    'domain_name' => $domain->name,
                    'years' => $years,
                ]);

                // Execute the renewal
                $result = $renewAction->handle($domain, $years, $this->order);

                if (! $result['success']) {
                    $allSuccessful = false;
                    $failedDomains[] = $domain->name;
                    Log::error('Domain renewal failed', [
                        'domain_id' => $domain->id,
                        'domain_name' => $domain->name,
                        'error' => $result['message'] ?? 'Unknown error',
                    ]);
                } else {
                    Log::info('Domain renewed successfully', [
                        'domain_id' => $domain->id,
                        'domain_name' => $domain->name,
                        'new_expiry' => $result['new_expiry'] ?? 'unknown',
                    ]);
                }
            }

            // Update order status based on results
            if ($allSuccessful) {
                $this->order->update([
                    'status' => 'completed',
                ]);

                Log::info('All domain renewals completed successfully', [
                    'order_id' => $this->order->id,
                    'order_number' => $this->order->order_number,
                ]);
            } else {
                $this->order->update([
                    'status' => 'partially_completed',
                    'notes' => 'Some domains failed to renew: '.implode(', ', $failedDomains),
                ]);

                Log::warning('Order partially completed - some renewals failed', [
                    'order_id' => $this->order->id,
                    'order_number' => $this->order->order_number,
                    'failed_domains' => $failedDomains,
                ]);

                // TODO: Send notification to user about failed renewals
                // TODO: Consider creating support tickets for failed renewals
            }

        } catch (Exception $e) {
            Log::error('Exception in ProcessDomainRenewalJob', [
                'order_id' => $this->order->id,
                'order_number' => $this->order->order_number,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Update order status to failed
            $this->order->update([
                'status' => 'failed',
                'notes' => 'Renewal processing failed: '.$e->getMessage(),
            ]);

            // Re-throw to trigger job retry
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(?Exception $exception): void
    {
        Log::error('ProcessDomainRenewalJob failed after all retries', [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'error' => $exception?->getMessage() ?? 'Unknown error',
        ]);

        // Update order status to failed
        $this->order->update([
            'status' => 'failed',
            'notes' => 'Renewal processing failed after multiple attempts: '.($exception?->getMessage() ?? 'Unknown error'),
        ]);

        // TODO: Send notification to admin about failed job
        // TODO: Consider creating a support ticket
    }
}
