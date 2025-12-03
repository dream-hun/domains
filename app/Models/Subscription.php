<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use HasFactory;
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'product_snapshot' => 'array',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'next_renewal_at' => 'datetime',
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

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
}
