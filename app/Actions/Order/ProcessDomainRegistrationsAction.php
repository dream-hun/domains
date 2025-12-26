<?php

declare(strict_types=1);

namespace App\Actions\Order;

use App\Jobs\ProcessDomainRegistrationJob;
use App\Jobs\ProcessSubscriptionRenewalJob;
use App\Models\Order;
use App\Services\HostingSubscriptionService;
use App\Services\NotificationService;
use App\Services\RenewalService;
use Exception;
use Illuminate\Support\Facades\Log;

final readonly class ProcessDomainRegistrationsAction
{
    public function __construct(
        private RenewalService $renewalService,
        private HostingSubscriptionService $hostingSubscriptionService,
        private NotificationService $notificationService
    ) {}

    /**
     * Process domain registrations for an order
     *
     * @param  array<string, int>  $contactIds
     */
    public function handle(Order $order, array $contactIds = []): void
    {
        $order->update(['status' => 'processing']);

        try {
            if ($order->type === 'subscription_renewal') {
                Log::info('Processing subscription renewal order', ['order_id' => $order->id]);
                dispatch(new ProcessSubscriptionRenewalJob($order));
            } elseif ($order->type === 'renewal') {
                Log::info('Processing renewal order', ['order_id' => $order->id]);
                $results = $this->renewalService->processDomainRenewals($order);

                if (empty($results['failed'])) {
                    $order->update(['status' => 'completed']);
                } elseif (empty($results['successful'])) {
                    $order->update(['status' => 'failed']);
                    $this->notificationService->notifyAdminOfFailedRegistration($order, $results);
                } else {
                    $order->update(['status' => 'partially_completed']);
                    $this->notificationService->notifyAdminOfPartialFailure($order, $results);
                }
            } elseif ($order->type === 'hosting') {
                Log::info('Processing hosting-only order', ['order_id' => $order->id]);
                $order->update(['status' => 'completed']);
            } else {
                throw_unless(
                    isset($contactIds['registrant'], $contactIds['admin'], $contactIds['tech'], $contactIds['billing']),
                    Exception::class,
                    'All contact IDs (registrant, admin, tech, billing) are required for domain registration.'
                );

                Log::info('Dispatching domain registration job', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                ]);

                dispatch(new ProcessDomainRegistrationJob($order, $contactIds));
            }

            $this->hostingSubscriptionService->createSubscriptionsFromOrder($order);

        } catch (Exception $exception) {
            Log::error('Order processing failed', [
                'order_id' => $order->id,
                'order_type' => $order->type,
                'error' => $exception->getMessage(),
            ]);

            $order->update([
                'status' => 'requires_attention',
                'notes' => 'Payment succeeded but processing failed: '.$exception->getMessage(),
            ]);

            $this->notificationService->notifyAdminOfCriticalFailure($order, $exception);
        }
    }
}
