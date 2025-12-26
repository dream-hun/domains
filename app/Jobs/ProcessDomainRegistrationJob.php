<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Order;
use App\Services\DomainRegistrationService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

final class ProcessDomainRegistrationJob implements ShouldQueue
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
        public Order $order,
        public array $contactIds = []
    ) {}

    /**
     * Execute the job.
     *
     * @throws Exception
     */
    public function handle(DomainRegistrationService $service): void
    {
        try {
            $this->order->update(['status' => 'processing']);

            Log::info('Processing domain registration job', [
                'order_id' => $this->order->id,
                'order_number' => $this->order->order_number,
            ]);

            $contacts = [
                'registrant' => $this->contactIds['registrant'],
                'admin' => $this->contactIds['admin'],
                'technical' => $this->contactIds['tech'],
                'billing' => $this->contactIds['billing'],
            ];

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
