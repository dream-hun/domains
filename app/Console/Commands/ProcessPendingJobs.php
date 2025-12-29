<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Order\ProcessOrderAfterPaymentAction;
use App\Models\Order;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

final class ProcessPendingJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:process-pending-jobs
                            {--limit=50 : Maximum number of orders to process}
                            {--older-than=30 : Only process orders older than N minutes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Process orders that are paid but haven't been fully processed yet";

    /**
     * Execute the console command.
     */
    public function handle(ProcessOrderAfterPaymentAction $processOrderAction): int
    {
        $limit = (int) $this->option('limit');
        $olderThanMinutes = (int) $this->option('older-than');

        $this->info('Finding pending orders to process...');

        // Find orders that are paid but haven't been processed
        // These are orders where:
        // - payment_status = 'paid'
        // - status is 'pending' or 'processing' (not completed/failed)
        // - processed_at is null OR older than the threshold
        $cutoffTime = now()->subMinutes($olderThanMinutes);

        $orders = Order::query()
            ->where('payment_status', 'paid')
            ->whereIn('status', ['pending', 'processing'])
            ->where(function ($query) use ($cutoffTime): void {
                $query->whereNull('processed_at')
                    ->orWhere('processed_at', '<=', $cutoffTime);
            })->oldest()
            ->limit($limit)
            ->get();

        if ($orders->isEmpty()) {
            $this->info('No pending orders found to process.');

            return self::SUCCESS;
        }

        $this->info(sprintf('Found %d order(s) to process.', $orders->count()));

        $processed = 0;
        $failed = 0;

        foreach ($orders as $order) {
            try {
                $this->line(sprintf('Processing order: %s (ID: %d)', $order->order_number, $order->id));

                // Get primary contact for the order
                $primaryContact = $order->user->contacts()->where('is_primary', true)->first();
                $contactIds = [];
                if ($primaryContact) {
                    $contactIds = [
                        'registrant' => $primaryContact->id,
                        'admin' => $primaryContact->id,
                        'tech' => $primaryContact->id,
                        'billing' => $primaryContact->id,
                    ];
                }

                // Process the order (idempotent operation)
                $processOrderAction->handle($order, $contactIds, false);

                // Update processed_at timestamp
                $order->update(['processed_at' => now()]);

                $processed++;
                $this->info(sprintf('✓ Successfully processed order: %s', $order->order_number));

            } catch (Exception $exception) {
                $failed++;
                $this->error(sprintf('✗ Failed to process order %s: %s', $order->order_number, $exception->getMessage()));

                Log::error('ProcessPendingJobs: Failed to process order', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'error' => $exception->getMessage(),
                    'trace' => $exception->getTraceAsString(),
                ]);
            }
        }

        $this->newLine();
        $this->info(sprintf('Processing complete: %d succeeded, %d failed', $processed, $failed));

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
