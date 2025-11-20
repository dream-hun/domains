<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\HostingPromotionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HostingPromotion extends Model
{
    /** @use HasFactory<HostingPromotionFactory> */
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'discount_percentage' => 'float',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(HostingPlan::class, 'hosting_plan_id');
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
