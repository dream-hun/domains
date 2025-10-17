<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Coupons\CouponType;
use Illuminate\Database\Eloquent\Model;

final class Coupon extends Model
{
    protected $guarded = [];

    protected $table = 'coupons';

    protected $casts = [
        'valid_from' => 'datetime',
        'valid_to' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'type' => CouponType::class,
    ];
}
