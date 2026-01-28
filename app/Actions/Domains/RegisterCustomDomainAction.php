<?php

declare(strict_types=1);

namespace App\Actions\Domains;

use App\Actions\RegisterDomainAction;
use App\Actions\Subscription\CreateCustomSubscriptionAction;
use App\Models\Domain;
use App\Models\Subscription;
use App\Services\CurrencyService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final readonly class RegisterCustomDomainAction
{
    public function __construct(
        private RegisterDomainAction $registerDomainAction,
        private CreateCustomSubscriptionAction $createCustomSubscriptionAction,
        private CurrencyService $currencyService
    ) {}

    /**
     * Register a domain with custom pricing and optionally create/link a subscription.
     *
     * @param  array<string, mixed>  $data
     * @return array{success: bool, message: string, domain_id?: int, subscription_id?: int}
     */
    public function handle(array $data, int $adminId): array
    {
        try {
            return DB::transaction(function () use ($data, $adminId): array {
                $contacts = [
                    'registrant' => $data['registrant_contact_id'],
                    'admin' => $data['admin_contact_id'],
                    'technical' => $data['technical_contact_id'],
                    'billing' => $data['billing_contact_id'],
                ];

                $nameservers = array_filter([
                    $data['nameserver_1'] ?? null,
                    $data['nameserver_2'] ?? null,
                    $data['nameserver_3'] ?? null,
                    $data['nameserver_4'] ?? null,
                ]);

                $registrationResult = $this->registerDomainAction->handle(
                    $data['domain_name'],
                    $contacts,
                    (int) $data['years'],
                    $nameservers,
                    false,
                    (int) $data['user_id']
                );

                if (! $registrationResult['success']) {
                    return $registrationResult;
                }

                $domain = Domain::query()->find($registrationResult['domain_id']);
                throw_unless($domain, Exception::class, 'Domain was registered but record not found in database.');

                $this->applyCustomPricing($domain, $data, $adminId);
                $subscriptionId = $this->handleSubscriptionOption($domain, $data, $adminId);

                Log::info('Custom domain registration completed', [
                    'domain_id' => $domain->id,
                    'domain_name' => $domain->name,
                    'admin_id' => $adminId,
                    'is_custom_price' => $domain->is_custom_price,
                    'subscription_id' => $subscriptionId,
                ]);

                return [
                    'success' => true,
                    'message' => sprintf('Domain %s registered successfully.', $domain->name),
                    'domain_id' => $domain->id,
                    'subscription_id' => $subscriptionId,
                ];
            });
        } catch (Exception $exception) {
            Log::error('Custom domain registration failed', [
                'domain_name' => $data['domain_name'] ?? 'unknown',
                'admin_id' => $adminId,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Domain registration failed: '.$exception->getMessage(),
            ];
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function applyCustomPricing(Domain $domain, array $data, int $adminId): void
    {
        $hasCustomPrice = isset($data['domain_custom_price'])
            && $data['domain_custom_price'] > 0;

        if (! $hasCustomPrice) {
            return;
        }

        $inputPrice = (float) $data['domain_custom_price'];
        $inputCurrency = $data['domain_custom_price_currency'] ?? 'USD';
        $customPriceUsd = $inputPrice;

        if ($inputCurrency !== 'USD') {
            try {
                $customPriceUsd = $this->currencyService->convert($inputPrice, $inputCurrency, 'USD');
            } catch (Exception $e) {
                throw new Exception(sprintf('Failed to convert custom price from %s to USD: %s', $inputCurrency, $e->getMessage()), $e->getCode(), $e);
            }
        }

        $domain->update([
            'custom_price' => $customPriceUsd,
            'custom_price_currency' => $inputCurrency,
            'is_custom_price' => true,
            'custom_price_notes' => $data['domain_custom_price_notes'] ?? null,
            'created_by_admin_id' => $adminId,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function handleSubscriptionOption(Domain $domain, array $data, int $adminId): ?int
    {
        $subscriptionOption = $data['subscription_option'] ?? 'none';

        return match ($subscriptionOption) {
            'create_new' => $this->createNewSubscription($domain, $data, $adminId),
            'link_existing' => $this->linkExistingSubscription($domain, $data),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createNewSubscription(Domain $domain, array $data, int $adminId): int
    {
        $subscriptionData = [
            'user_id' => $data['user_id'],
            'hosting_plan_id' => $data['hosting_plan_id'],
            'billing_cycle' => $data['billing_cycle'],
            'domain' => $domain->name,
            'starts_at' => $data['hosting_starts_at'],
            'expires_at' => $data['hosting_expires_at'],
            'auto_renew' => $data['hosting_auto_renew'] ?? false,
        ];

        if (isset($data['hosting_custom_price']) && $data['hosting_custom_price'] > 0) {
            $subscriptionData['custom_price'] = $data['hosting_custom_price'];
            $subscriptionData['custom_price_currency'] = $data['hosting_custom_price_currency'] ?? 'USD';
            $subscriptionData['custom_price_notes'] = $data['hosting_custom_price_notes'] ?? null;
        }

        $subscription = $this->createCustomSubscriptionAction->handle($subscriptionData, $adminId);
        $domain->update(['subscription_id' => $subscription->id]);

        Log::info('New hosting subscription created for domain', [
            'domain_id' => $domain->id,
            'subscription_id' => $subscription->id,
            'has_custom_price' => isset($data['hosting_custom_price']),
        ]);

        return $subscription->id;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function linkExistingSubscription(Domain $domain, array $data): int
    {
        $subscriptionId = (int) $data['existing_subscription_id'];
        $subscription = Subscription::query()->findOrFail($subscriptionId);

        $domain->update(['subscription_id' => $subscription->id]);

        if (empty($subscription->domain)) {
            $subscription->update(['domain' => $domain->name]);
        }

        Log::info('Domain linked to existing subscription', [
            'domain_id' => $domain->id,
            'subscription_id' => $subscription->id,
        ]);

        return $subscription->id;
    }
}
