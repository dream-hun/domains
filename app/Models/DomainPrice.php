<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DomainType;
use App\Models\Scopes\DomainPriceScope;
use Cknow\Money\Money;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
#[ScopedBy(DomainPriceScope::class)]
final class DomainPrice extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'register_price' => 'integer',
        'renewal_price' => 'integer',
        'transfer_price' => 'integer',
        'redemption_price' => 'integer',
        'grace_period' => 'integer',
        'type' => DomainType::class,
    ];

    public function getFormattedPrice(string $priceType = 'register_price'): string
    {
        $priceAmount = $this->{$priceType};

        $currency = $this->type === DomainType::Local ? 'RWF' : 'USD';

        return (new Money($priceAmount, $currency))->format();
    }
}
