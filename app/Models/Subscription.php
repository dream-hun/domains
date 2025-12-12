<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Support\Carbon;
use App\Enums\Hosting\BillingCycle;
use Exception;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\DatabaseNotificationCollection;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Date;

/**
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property int $hosting_plan_id
 * @property int $hosting_plan_price_id
 * @property array $product_snapshot
 * @property string $billing_cycle
 * @property string $domain
 * @property string $status
 * @property Carbon $starts_at
 * @property Carbon $expires_at
 * @property Carbon|null $next_renewal_at
 * @property string|null $provider_resource_id
 * @property Carbon|null $cancelled_at
 * @property bool $auto_renew
 * @property Carbon|null $last_renewal_attempt_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read User|null $user
 * @property-read HostingPlan $plan
 * @property-read HostingPlanPrice $planPrice
 * @property-read DatabaseNotificationCollection<int, DatabaseNotification> $notifications
 * @property-read DatabaseNotificationCollection<int, DatabaseNotification> $unreadNotifications
 */
class Subscription extends Model
{
    use HasFactory;

    use Notifiable;

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
        if ($this->status !== 'active') {
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
                    sprintf('No active pricing found for plan %s with billing cycle %s', $this->hosting_plan_id, $billingCycle->value)
                );
            }

            $expectedAmount = $planPrice->getPriceInBaseCurrency('renewal_price');

            if (abs($paidAmount - $expectedAmount) > 0.01) {
                throw new Exception(
                    sprintf('Payment amount mismatch. Expected: %s, Paid: %s for billing cycle %s', $expectedAmount, $paidAmount, $billingCycle->value)
                );
            }
        }

        $currentExpiry = $this->expires_at ?? Date::now();

        $newExpiry = match ($billingCycle) {
            BillingCycle::Annually => $currentExpiry->copy()->addYear(),
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

    /**
     * Extend subscription by a specific number of months
     * Used for renewals where quantity represents months
     *
     * @throws Exception
     */
    public function extendSubscriptionByMonths(
        int $months,
        ?float $paidAmount = null,
        bool $isComp = false,
        ?array $renewalSnapshot = null
    ): void {
        if ($paidAmount !== null && ! $isComp) {
            // Get monthly plan price for validation
            $monthlyPlanPrice = HostingPlanPrice::query()
                ->where('hosting_plan_id', $this->hosting_plan_id)
                ->where('billing_cycle', 'monthly')
                ->where('status', 'active')
                ->first();

            if (! $monthlyPlanPrice) {
                throw new Exception(
                    sprintf('No active monthly pricing found for plan %s', $this->hosting_plan_id)
                );
            }

            $expectedMonthlyPrice = $monthlyPlanPrice->getPriceInBaseCurrency('renewal_price');
            $expectedTotalAmount = $expectedMonthlyPrice * $months;

            // Use a more lenient tolerance (0.50) to account for currency conversion rounding
            // Currency conversions can introduce small rounding differences (typically 0.01-0.05)
            $tolerance = 0.50;
            $difference = abs($paidAmount - $expectedTotalAmount);

            if ($difference > $tolerance) {
                throw new Exception(
                    sprintf('Payment amount mismatch. Expected: %s (monthly price %s × %d months), Paid: %s (difference: %s)', $expectedTotalAmount, $expectedMonthlyPrice, $months, $paidAmount, $difference)
                );
            }
        }

        $currentExpiry = $this->expires_at ?? Date::now();

        $newExpiry = $currentExpiry->copy()->addMonths($months);

        $snapshot = $this->product_snapshot ?? [];
        if ($renewalSnapshot !== null) {
            $snapshot['renewals'][] = [
                'renewed_at' => Date::now()->toIso8601String(),
                'months' => $months,
                'paid_amount' => $paidAmount,
                'is_comp' => $isComp,
                'price' => $renewalSnapshot,
            ];
        }

        $updateData = [
            'expires_at' => $newExpiry,
            'next_renewal_at' => $newExpiry,
            'status' => 'active',
            'product_snapshot' => $snapshot,
        ];

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
