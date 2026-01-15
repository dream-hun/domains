<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    use HasFactory;

    protected $guarded = [];

    /** @return BelongsTo<User,$this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'full_name' => 'string',
            'company' => 'string',
            'address_line_one' => 'string',
            'address_line_two' => 'string',
            'city' => 'string',
            'state' => 'string',
            'country_code' => 'string',
            'postal_code' => 'string',
            'phone_number' => 'string',
        ];

    }
}
