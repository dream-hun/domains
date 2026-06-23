<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Domain;
use App\Notifications\RenewalInvoiceNotification;
use App\Services\DomainInvoiceGenerationService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Support\Facades\Log;

#[Backoff(60)]
#[Tries(3)]
final class GenerateDomainRenewalInvoiceJob implements ShouldQueue
{
    use Queueable;

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
