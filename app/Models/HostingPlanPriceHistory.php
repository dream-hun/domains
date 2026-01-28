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
        return $this->belongsTo(HostingPlanPrice::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    protected function casts(): array
    {
        return [
            'hosting_plan_price_id' => 'integer',
            'changed_by' => 'integer',
            'reason' => 'string',
            'regular_price' => 'integer',
            'renewal_price' => 'integer',
            'changes' => 'array',
            'old_values' => 'array',
            'ip_address' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
