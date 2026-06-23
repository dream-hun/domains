<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Coupons\CouponType;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Table(name: 'coupons')]
final class Coupon extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'valid_from' => 'datetime',
        'valid_to' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'type' => CouponType::class,
    ];
}
