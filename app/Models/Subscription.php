<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Hosting\BillingCycle;
use Exception;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Date;

class Subscription extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'product_snapshot' => 'array',
        'auto_renew' => 'boolean',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'next_renewal_at' => 'datetime',
        'last_renewal_attempt_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(HostingPlan::class, 'hosting_plan_id');
    }

    public function planPrice(): BelongsTo
    {
        return $this->belongsTo(HostingPlanPrice::class, 'hosting_plan_price_id');
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function isExpiringSoon(int $days = 7): bool
    {
        if ($this->status !== 'active' || ! $this->expires_at) {
            return false;
        }

        $now = Date::now();
        $expiryThreshold = $now->copy()->addDays($days);

        return $this->expires_at->lessThanOrEqualTo($expiryThreshold)
            && $this->expires_at->greaterThanOrEqualTo($now);
    }

    public function canBeRenewed(): bool
    {
        return in_array($this->status, ['active', 'expired'], true);
    }

    /**
     * @throws Exception
     */
    public function extendSubscription(
        BillingCycle $billingCycle,
        ?float $paidAmount = null,
        bool $validatePayment = true,
        bool $isComp = false,
        ?array $renewalSnapshot = null
    ): void {
        if ($validatePayment && $paidAmount !== null && ! $isComp) {
            $planPrice = HostingPlanPrice::query()
                ->where('hosting_plan_id', $this->hosting_plan_id)
                ->where('billing_cycle', $billingCycle->value)
                ->where('status', 'active')
                ->first();

            if (! $planPrice) {
                throw new Exception(
                    "No active pricing found for plan {$this->hosting_plan_id} with billing cycle {$billingCycle->value}"
                );
            }

            $expectedAmount = $planPrice->getPriceInBaseCurrency('renewal_price');

            if (abs($paidAmount - $expectedAmount) > 0.01) {
                throw new Exception(
                    "Payment amount mismatch. Expected: {$expectedAmount}, Paid: {$paidAmount} for billing cycle {$billingCycle->value}"
                );
            }
        }

        $currentExpiry = $this->expires_at ?? Date::now();

        $newExpiry = match ($billingCycle) {
            BillingCycle::Monthly => $currentExpiry->copy()->addMonth(),
            BillingCycle::Quarterly => $currentExpiry->copy()->addMonths(3),
            BillingCycle::SemiAnnually => $currentExpiry->copy()->addMonths(6),
            BillingCycle::Annually => $currentExpiry->copy()->addYear(),
            BillingCycle::Biennially => $currentExpiry->copy()->addYears(2),
            BillingCycle::Triennially => $currentExpiry->copy()->addYears(3),
            default => $currentExpiry->copy()->addMonth(),
        };

        $snapshot = $this->product_snapshot ?? [];
        if ($renewalSnapshot !== null) {
            $snapshot['renewals'][] = [
                'renewed_at' => Date::now()->toIso8601String(),
                'billing_cycle' => $billingCycle->value,
                'paid_amount' => $paidAmount,
                'is_comp' => $isComp,
                'price' => $renewalSnapshot,
            ];
        } else {
            $planPrice = HostingPlanPrice::query()
                ->where('hosting_plan_id', $this->hosting_plan_id)
                ->where('billing_cycle', $billingCycle->value)
                ->first();

            if ($planPrice) {
                $snapshot['price'] = [
                    'id' => $planPrice->id,
                    'regular_price' => $planPrice->regular_price,
                    'renewal_price' => $planPrice->renewal_price,
                    'billing_cycle' => $planPrice->billing_cycle,
                    'updated_at' => Date::now()->toIso8601String(),
                ];
            }
        }

        $updateData = [
            'expires_at' => $newExpiry,
            'next_renewal_at' => $newExpiry,
            'status' => 'active',
            'product_snapshot' => $snapshot,
        ];

        if ($this->billing_cycle !== $billingCycle->value) {
            $updateData['billing_cycle'] = $billingCycle->value;
        }

        $this->update($updateData);
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    #[Scope]
    protected function expiringSoon(Builder $query, int $days = 30): Builder
    {
        $now = Date::now();
        $endDate = $now->copy()->addDays($days);

        return $query->where('status', 'active')
            ->whereBetween('expires_at', [$now, $endDate]);
    }

    #[Scope]
    protected function autoRenewable(Builder $query): Builder
    {
        return $query->where('auto_renew', true)
            ->where('status', 'active');
    }
}
