<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Hosting\BillingCycle;
use App\Models\HostingPlan;
use App\Models\OrderItem;
use Illuminate\Support\Str;

final readonly class OrderItemFormatterService
{
    /**
     * Get display name for OrderItem or cart item
     */
    public function getItemDisplayName(OrderItem $item): string
    {
        return $this->getOrderItemDisplayName($item);
    }

    public function getItemPeriod(OrderItem $item): string
    {
        return $this->getOrderItemPeriod($item);
    }

    /**
     * Get period for cart item
     */
    public function getCartItemPeriod(object $item): string
    {
        $itemType = $item->attributes->get('type', 'registration');
        $item->attributes->get('metadata', []);

        if ($itemType === 'subscription_renewal') {
            $durationMonths = $item->attributes->get('duration_months');

            if (! $durationMonths && $item->quantity) {
                $durationMonths = $item->quantity;
            }

            if ($durationMonths) {
                return $this->formatDurationLabel((int) $durationMonths).' renewal';
            }

            $billingCycle = $item->attributes->get('billing_cycle');
            if ($billingCycle) {
                $billingCycleEnum = BillingCycle::tryFrom($billingCycle);
                if ($billingCycleEnum) {
                    return $this->formatBillingCycleLabel($billingCycleEnum).' renewal';
                }
            }
        }

        if ($itemType === 'hosting') {
            $billingCycle = $item->attributes->get('billing_cycle');

            if ($billingCycle) {
                $billingCycleEnum = BillingCycle::tryFrom($billingCycle);
                if ($billingCycleEnum) {
                    return $this->formatBillingCycleLabel($billingCycleEnum);
                }
            }
        }

        $years = $item->quantity ?? 1;
        $suffix = ($itemType === 'renewal') ? 'renewal' : 'of registration';

        return $years.' '.Str::plural('year', $years).' '.$suffix;
    }

    public function formatBillingCycleLabel(BillingCycle $cycle): string
    {
        return match ($cycle) {
            BillingCycle::Monthly => '1 month',
            BillingCycle::Annually => '1 year',
        };
    }

    /**
     * Format duration in months to readable label
     */
    public function formatDurationLabel(int $months): string
    {
        if ($months < 12) {
            return $months.' '.Str::plural('month', $months);
        }

        $years = (int) ($months / 12);

        return $years.' '.Str::plural('year', $years);
    }

    /**
     * Get display name for cart item
     */
    public function getCartItemDisplayName(object $item): string
    {
        $itemType = $item->attributes->get('type', 'registration');
        $itemName = $item->attributes->get('domain_name') ?? $item->name ?? '';

        if (in_array($itemType, ['subscription_renewal', 'hosting'], true)) {
            $metadata = $item->attributes->get('metadata', []);
            $hostingPlanId = $metadata['hosting_plan_id'] ?? $item->attributes->get('hosting_plan_id');

            if ($hostingPlanId) {
                $plan = HostingPlan::query()->find($hostingPlanId);

                if ($plan && $plan->name) {
                    return $plan->name;
                }
            }

            $planData = $metadata['plan'] ?? null;

            if ($planData && isset($planData['name']) && $planData['name'] !== 'N/A') {
                return $planData['name'];
            }

            if ($itemName && str_contains((string) $itemName, ' - ')) {
                $parts = explode(' - ', (string) $itemName, 2);
                if (count($parts) === 2) {
                    $planPart = $parts[1];
                    $planPart = preg_replace('/\s*\(Renewal\)\s*$/i', '', $planPart);
                    $planPart = mb_trim($planPart);

                    if ($planPart && $planPart !== 'N/A') {
                        return $planPart;
                    }
                }
            }

            if ($itemName && str_contains((string) $itemName, ' Hosting (')) {
                $planName = str_replace(' Hosting (', '', $itemName);
                $planName = preg_replace('/\s*\([^)]*\)\s*$/', '', $planName);
                $planName = mb_trim($planName);

                if ($planName && $planName !== 'N/A') {
                    return $planName;
                }
            }

            if ($itemName && $itemName !== 'N/A') {
                $cleaned = preg_replace('/^(N\/A|N\/A\s*-\s*|Hosting\s*-\s*)/i', '', (string) $itemName);
                $cleaned = mb_trim($cleaned);

                if ($cleaned && $cleaned !== 'N/A') {
                    return $cleaned;
                }
            }
        }

        if ($itemName && $itemName !== 'N/A') {
            return $itemName;
        }

        return 'Item';
    }

    private function getOrderItemDisplayName(OrderItem $item): string
    {
        $itemType = $item->domain_type ?? 'registration';
        $itemName = $item->domain_name ?? '';
        if (in_array($itemType, ['subscription_renewal', 'hosting'], true)) {
            $metadata = $item->metadata ?? [];
            $hostingPlanId = $metadata['hosting_plan_id'] ?? null;

            if ($hostingPlanId) {
                $plan = HostingPlan::query()->find($hostingPlanId);

                if ($plan && $plan->name) {
                    return $plan->name;
                }
            }

            $planData = $metadata['plan'] ?? null;

            if ($planData && isset($planData['name']) && $planData['name'] !== 'N/A') {
                return $planData['name'];
            }

            if ($itemName && str_contains((string) $itemName, ' - ')) {
                $parts = explode(' - ', (string) $itemName, 2);
                if (count($parts) === 2) {
                    $planPart = $parts[1];
                    $planPart = preg_replace('/\s*\(Renewal\)\s*$/i', '', $planPart);
                    $planPart = mb_trim($planPart);

                    if ($planPart && $planPart !== 'N/A') {
                        return $planPart;
                    }
                }
            }

            if ($itemName && str_contains((string) $itemName, ' Hosting (')) {
                $planName = str_replace(' Hosting (', '', $itemName);
                $planName = preg_replace('/\s*\([^)]*\)\s*$/', '', $planName);
                $planName = mb_trim($planName);

                if ($planName && $planName !== 'N/A') {
                    return $planName;
                }
            }

            if ($itemName && $itemName !== 'N/A') {
                $cleaned = preg_replace('/^(N\/A|N\/A\s*-\s*|Hosting\s*-\s*)/i', '', (string) $itemName);
                $cleaned = mb_trim($cleaned);

                if ($cleaned && $cleaned !== 'N/A') {
                    return $cleaned;
                }
            }
        }

        if ($itemName && $itemName !== 'N/A') {
            return $itemName;
        }

        return 'Item';
    }

    private function getOrderItemPeriod(OrderItem $item): string
    {
        $itemType = $item->domain_type ?? 'registration';
        $metadata = $item->metadata ?? [];

        if ($itemType === 'subscription_renewal') {
            $durationMonths = $metadata['duration_months'] ?? null;

            if (! $durationMonths && $item->quantity) {
                $durationMonths = $item->quantity;
            }

            if ($durationMonths) {
                return $this->formatDurationLabel((int) $durationMonths).' renewal';
            }

            $billingCycle = $metadata['billing_cycle'] ?? null;
            if ($billingCycle) {
                $billingCycleEnum = BillingCycle::tryFrom($billingCycle);
                if ($billingCycleEnum) {
                    return $this->formatBillingCycleLabel($billingCycleEnum).' renewal';
                }
            }
        }

        if ($itemType === 'hosting') {
            $billingCycle = $metadata['billing_cycle'] ?? null;

            if ($billingCycle) {
                $billingCycleEnum = BillingCycle::tryFrom($billingCycle);

                if ($billingCycleEnum) {
                    return $this->formatBillingCycleLabel($billingCycleEnum);
                }
            }
        }

        $years = $item->years ?? $item->quantity ?? 1;
        $suffix = ($itemType === 'renewal') ? 'renewal' : 'of registration';

        return $years.' '.Str::plural('year', $years).' '.$suffix;
    }
}
