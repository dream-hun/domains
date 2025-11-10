<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TransferStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Transfer extends Model
{
    use HasFactory;

    protected $casts = [
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
        'status' => TransferStatus::class,
        'token' => 'encrypted',
        'auth_code' => 'encrypted',

    ];

    protected $guarded = [];

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
