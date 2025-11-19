<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Hosting\HostingPlanPriceStatus;
use Database\Factories\HostingPlanPriceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HostingPlanPrice extends Model
{
    /** @use HasFactory<HostingPlanPriceFactory> */
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'regular_price' => 'integer',
        'promotional_price' => 'integer',
        'renewal_price' => 'integer',
        'discount_percentage' => 'integer',
        'promotional_start_date' => 'date',
        'promotional_end_date' => 'date',
        'status' => HostingPlanPriceStatus::class,
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
