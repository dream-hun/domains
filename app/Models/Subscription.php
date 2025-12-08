<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Hosting\BillingCycle;
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

    public function extendSubscription(BillingCycle $billingCycle): void
    {
        $currentExpiry = $this->expires_at ?? Date::now();

        $newExpiry = match ($billingCycle) {
            BillingCycle::Monthly => $currentExpiry->copy()->addMonth(),
            BillingCycle::Quarterly => $currentExpiry->copy()->addMonths(3),
            BillingCycle::SemiAnnually => $currentExpiry->copy()->addMonths(6),
            BillingCycle::Annually => $currentExpiry->copy()->addYear(),
            BillingCycle::Biennially => $currentExpiry->copy()->addYears(2),
            BillingCycle::Triennially => $currentExpiry->copy()->addYears(3),
        };

        $this->update([
            'expires_at' => $newExpiry,
            'next_renewal_at' => $newExpiry,
            'status' => 'active',
        ]);
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
