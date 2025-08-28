<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Nameserver extends Model
{
    protected $guarded = [];

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }
}
