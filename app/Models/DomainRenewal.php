<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class DomainRenewal extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'old_expiry_date' => 'date',
            'new_expiry_date' => 'date',
        ];
    }
}
