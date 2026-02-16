<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HostingPlanPriceHistory extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function hostingPlanPrice(): BelongsTo
    {
        return $this->belongsTo(HostingPlanPrice::class, 'hosting_plan_pricing_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    protected function casts(): array
    {
        return [
            'hosting_plan_pricing_id' => 'integer',
            'changed_by' => 'integer',
            'reason' => 'string',
            'regular_price' => 'float',
            'renewal_price' => 'float',
            'changes' => 'array',
            'old_values' => 'array',
            'ip_address' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
