<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\RenewDomainAction;
use App\Enums\OrderStatus;
use App\Models\Domain;
use App\Models\Order;
use App\Notifications\DomainAutoRenewalFailedNotification;
use App\Notifications\DomainRenewalNotification;
use App\Services\IdempotencyService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Support\Facades\Log;

#[Backoff(60)]
#[Timeout(120)]
#[Tries(3)]
final class ProcessDomainRenewalJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Order $order
    ) {
        $this->onQueue('critical');
    }

    /**
     * Execute the job.
     */
    public function handle(RenewDomainAction $renewAction, IdempotencyService $idempotency): void
    {
        $idempotency->once('domain-renewal:order-'.$this->order->id, function () use ($renewAction): void {
            $this->process($renewAction);
        });
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

    private function process(RenewDomainAction $renewAction): void
    {
        try {
            $this->notifyUserRenewalProcessing();

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

                $domainId = $this->resolveDomainIdFromItem($item);
                $years = $item['attributes']['years'] ?? $item['quantity'];

                // Find the domain (bypass global scopes since we're in a job context)
                $domain = Domain::query()->withoutGlobalScopes()->find($domainId);

                if (! $domain) {
                    Log::error('Domain not found for renewal', [
                        'domain_id' => $domainId,
                        'order_id' => $this->order->id,
                        'item_id' => $item['id'],
                        'item_attributes' => $item['attributes'] ?? [],
                    ]);
                    $allSuccessful = false;
                    $failedDomains[] = $item['name'] ?? 'Domain ID: '.$domainId;

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
                    'status' => OrderStatus::PartialCompleted->value,
                    'notes' => 'Some domains failed to renew: '.implode(', ', $failedDomains),
                ]);

                Log::warning('Order partially completed - some renewals failed', [
                    'order_id' => $this->order->id,
                    'order_number' => $this->order->order_number,
                    'failed_domains' => $failedDomains,
                ]);

                $this->notifyUserRenewalFailed($failedDomains);
            }

        } catch (Exception $exception) {
            Log::error('Exception in ProcessDomainRenewalJob', [
                'order_id' => $this->order->id,
                'order_number' => $this->order->order_number,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            // Update order status to failed
            $this->order->update([
                'status' => 'failed',
                'notes' => 'Renewal processing failed: '.$exception->getMessage(),
            ]);

            // Re-throw to trigger job retry
            throw $exception;
        }
    }

    /**
     * @return array<int, array{name: string, years: int}>
     */
    private function renewalItems(): array
    {
        return collect($this->order->items ?? [])
            ->filter(fn (array $item): bool => ($item['attributes']['type'] ?? null) === 'renewal')
            ->map(function (array $item): array {
                $years = (int) ($item['attributes']['years'] ?? $item['quantity'] ?? 1);

                return [
                    'name' => $item['name'] ?? 'Domain ID: '.($item['id'] ?? 'unknown'),
                    'years' => max($years, 1),
                ];
            })
            ->values()
            ->all();
    }

    private function resolveDomainIdFromItem(array $item): int
    {
        $id = $item['attributes']['domain_id'] ?? $item['id'];

        if (is_string($id) && str_starts_with($id, 'renewal-')) {
            $id = (int) str_replace('renewal-', '', $id);
        }

        return (int) $id;
    }

    /**
     * @param  array<int, string>  $failedDomains
     */
    private function notifyUserRenewalFailed(array $failedDomains): void
    {
        $this->order->loadMissing('user');

        $user = $this->order->user;

        if ($user === null) {
            return;
        }

        foreach ($failedDomains as $domainName) {
            $domain = Domain::query()->withoutGlobalScopes()->where('name', $domainName)->first();

            if ($domain) {
                $user->notify(new DomainAutoRenewalFailedNotification(
                    $domain,
                    'Automatic renewal processing failed. Please contact support.'
                ));
            }
        }
    }

    private function notifyUserRenewalProcessing(): void
    {
        $this->order->loadMissing('user');

        $user = $this->order->user;

        if ($user === null) {
            Log::warning('Skipping domain renewal notification because order has no associated user', [
                'order_id' => $this->order->id,
            ]);

            return;
        }

        $renewalItems = $this->renewalItems();

        if ($renewalItems === []) {
            Log::info('Skipping domain renewal notification because no renewal items were found', [
                'order_id' => $this->order->id,
            ]);

            return;
        }

        $user->notify(new DomainRenewalNotification($this->order, $renewalItems));
    }
}
