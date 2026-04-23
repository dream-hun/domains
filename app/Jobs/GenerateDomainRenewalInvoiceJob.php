<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Domain;
use App\Notifications\RenewalInvoiceNotification;
use App\Services\DomainInvoiceGenerationService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

final class GenerateDomainRenewalInvoiceJob implements ShouldQueue
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
        public Domain $domain
    ) {}

    /**
     * Execute the job.
     */
    public function handle(DomainInvoiceGenerationService $service): void
    {
        if ($service->hasPendingRenewalOrder($this->domain)) {
            Log::info('Skipping domain renewal invoice generation (pending order exists)', [
                'domain_id' => $this->domain->id,
                'domain_name' => $this->domain->name,
            ]);

            return;
        }

        $order = $service->createRenewalInvoiceOrder($this->domain);

        $this->domain->owner->notify(new RenewalInvoiceNotification($order));

        Log::info('Renewal invoice generated for domain', [
            'domain_id' => $this->domain->id,
            'domain_name' => $this->domain->name,
            'order_id' => $order->id,
            'expires_at' => $this->domain->expires_at?->toDateString(),
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(?Exception $exception): void
    {
        Log::error('GenerateDomainRenewalInvoiceJob failed after all retries', [
            'domain_id' => $this->domain->id,
            'domain_name' => $this->domain->name,
            'error' => $exception?->getMessage() ?? 'Unknown error',
        ]);
    }
}
