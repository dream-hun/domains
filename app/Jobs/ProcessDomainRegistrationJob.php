<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Order;
use App\Services\DomainRegistrationService;
use App\Services\IdempotencyService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Support\Facades\Log;
use Throwable;

#[Backoff(60)]
#[Timeout(120)]
#[Tries(3)]
final class ProcessDomainRegistrationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Order $order,
        public array $contactIds = []
    ) {
        $this->onQueue('critical');
    }

    /**
     * Handle a job failure after all retries are exhausted.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('Domain registration job permanently failed after all retries', [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'error' => $exception->getMessage(),
        ]);

        $this->order->update([
            'status' => 'failed',
            'notes' => 'Domain registration permanently failed after all retries: '.$exception->getMessage(),
        ]);
    }

    /**
     * Execute the job.
     *
     * @throws Exception
     */
    public function handle(DomainRegistrationService $service, IdempotencyService $idempotency): void
    {
        $idempotency->once('domain-registration:order-'.$this->order->id, function () use ($service): void {
            $this->process($service);
        });
    }

    /**
     * @throws Exception
     */
    private function process(DomainRegistrationService $service): void
    {
        try {
            $this->order->update(['status' => 'processing']);

            Log::info('Processing domain registration job', [
                'order_id' => $this->order->id,
                'order_number' => $this->order->order_number,
            ]);

            $fallback = $this->contactIds['registrant']
                ?? $this->contactIds['admin']
                ?? $this->contactIds['tech']
                ?? $this->contactIds['billing']
                ?? null;

            $contacts = [
                'registrant' => $this->contactIds['registrant'] ?? $fallback,
                'admin' => $this->contactIds['admin'] ?? $fallback,
                'technical' => $this->contactIds['tech'] ?? $fallback,
                'billing' => $this->contactIds['billing'] ?? $fallback,
            ];

            if ($fallback === null) {
                throw new Exception('No contact IDs provided for domain registration job (order '.$this->order->order_number.')');
            }

            $service->processDomainRegistrations($this->order, $contacts);

        } catch (Exception $exception) {
            Log::error('Domain registration job failed', [
                'order_id' => $this->order->id,
                'order_number' => $this->order->order_number,
                'error' => $exception->getMessage(),
            ]);

            $this->order->update([
                'status' => 'requires_attention',
                'notes' => 'Domain registration processing failed: '.$exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
