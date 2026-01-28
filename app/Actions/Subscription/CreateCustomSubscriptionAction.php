<?php

declare(strict_types=1);

namespace App\Actions\Subscription;

use App\Enums\Hosting\BillingCycle;
use App\Models\HostingPlan;
use App\Models\HostingPlanPrice;
use App\Models\Subscription;
use App\Models\User;
use App\Services\CurrencyService;
use Exception;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final readonly class CreateCustomSubscriptionAction
{
    public function __construct(
        private CurrencyService $currencyService
    ) {}

    /**
     * Create a custom subscription
     *
     * @param  array<string, mixed>  $data
     *
     * @throws Exception
     */
    public function handle(array $data, int $adminId): Subscription
    {
        $user = User::query()->findOrFail($data['user_id']);
        $plan = HostingPlan::query()->findOrFail($data['hosting_plan_id']);

        $billingCycle = $this->resolveBillingCycle($data['billing_cycle']);
        $planPrice = HostingPlanPrice::query()
            ->where('hosting_plan_id', $plan->id)
            ->where('billing_cycle', $billingCycle->value)
            ->where('status', 'active')
            ->first();

        if (! $planPrice) {
            throw new Exception(
                sprintf('No active pricing found for plan %s with billing cycle %s', $plan->id, $billingCycle->value)
            );
        }

        $customPrice = null;
        $customPriceCurrency = null;
        $isCustomPrice = false;

        if (isset($data['custom_price']) && $data['custom_price'] !== null && $data['custom_price'] > 0) {
            $isCustomPrice = true;
            $inputCurrency = $data['custom_price_currency'] ?? 'USD';
            $inputPrice = (float) $data['custom_price'];

            if ($inputCurrency !== 'USD') {
                try {
                    $customPrice = $this->currencyService->convert($inputPrice, $inputCurrency, 'USD');
                    $customPriceCurrency = $inputCurrency;
                } catch (Exception $e) {
                    throw new Exception(sprintf('Failed to convert custom price from %s to USD: %s', $inputCurrency, $e->getMessage()), $e->getCode(), $e);
                }
            } else {
                $customPrice = $inputPrice;
                $customPriceCurrency = 'USD';
            }
        }

        $startsAt = Date::parse($data['starts_at']);
        $expiresAt = Date::parse($data['expires_at']);

        $nextRenewalAt = match ($billingCycle) {
            BillingCycle::Annually => $expiresAt->copy(),
            default => $expiresAt->copy(),
        };

        $subscriptionData = [
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'hosting_plan_id' => $plan->id,
            'hosting_plan_price_id' => $planPrice->id,
            'product_snapshot' => [
                'plan' => [
                    'id' => $plan->id,
                    'name' => $plan->name,
                ],
                'price' => [
                    'id' => $planPrice->id,
                    'regular_price' => $planPrice->regular_price,
                    'renewal_price' => $planPrice->renewal_price,
                    'billing_cycle' => $planPrice->billing_cycle,
                ],
                'created_by_admin' => true,
                'admin_id' => $adminId,
            ],
            'billing_cycle' => $billingCycle->value,
            'domain' => $data['domain'] ?? null,
            'status' => 'active',
            'starts_at' => $startsAt,
            'expires_at' => $expiresAt,
            'next_renewal_at' => $nextRenewalAt,
            'auto_renew' => $data['auto_renew'] ?? false,
        ];

        if ($isCustomPrice) {
            $subscriptionData['custom_price'] = $customPrice;
            $subscriptionData['custom_price_currency'] = $customPriceCurrency;
            $subscriptionData['is_custom_price'] = true;
            $subscriptionData['created_by_admin_id'] = $adminId;
            $subscriptionData['custom_price_notes'] = $data['custom_price_notes'] ?? null;
        }

        $subscription = Subscription::query()->create($subscriptionData);

        Log::info('Custom subscription created by admin', [
            'subscription_id' => $subscription->id,
            'subscription_uuid' => $subscription->uuid,
            'admin_id' => $adminId,
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'is_custom_price' => $isCustomPrice,
            'custom_price' => $customPrice,
            'custom_price_currency' => $customPriceCurrency,
        ]);

        return $subscription;
    }

    private function resolveBillingCycle(string $cycle): BillingCycle
    {
        foreach (BillingCycle::cases() as $case) {
            if ($case->value === $cycle) {
                return $case;
            }
        }

        return BillingCycle::Monthly;
    }
}
