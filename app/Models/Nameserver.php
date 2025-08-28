<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Nameserver extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function domains(): BelongsTo
    {
        return $this->belongsTo(Domain::class, 'domain_id');
    }
}
