<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Address extends Model
{
    use HasFactory;

    protected $guarded = [];

    /** @return BelongsTo<User,$this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the preferred currency from user's previous successful payments.
     */
    public function getPreferredCurrencyFromPayments(): ?string
    {
        if (! $this->user_id) {
            return null;
        }

        $payment = Payment::query()
            ->where('user_id', $this->user_id)
            ->where('status', 'succeeded')
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->first();

        return $payment?->currency;
    }

    /**
     * Set preferred currency from payments if not already set.
     */
    public function setPreferredCurrencyIfNotSet(): void
    {
        if ($this->hasPreferredCurrency()) {
            return;
        }

        $currency = $this->getPreferredCurrencyFromPayments();

        if ($currency !== null) {
            $this->preferred_currency = $currency;
        }
    }

    protected static function boot(): void
    {
        parent::boot();

        self::saving(function (Address $address): void {
            $address->setPreferredCurrencyIfNotSet();
        });
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
            'preferred_currency' => 'string',
        ];

    }

    /**
     * Check if preferred currency is already set.
     */
    private function hasPreferredCurrency(): bool
    {
        return filled($this->preferred_currency);
    }
}
