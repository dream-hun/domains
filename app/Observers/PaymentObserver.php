<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Address;
use App\Models\Payment;

final class PaymentObserver
{
    /**
     * Handle the Payment "saved" event.
     * Update Address preferred_currency when payment succeeds.
     */
    public function saved(Payment $payment): void
    {
        if (! $this->shouldUpdateAddressCurrency($payment)) {
            return;
        }

        $address = Address::query()->where('user_id', $payment->user_id)->first();

        if ($address === null) {
            return;
        }

        if ($address->preferred_currency === $payment->currency) {
            return;
        }

        $address->preferred_currency = $payment->currency;
        $address->saveQuietly();
    }

    /**
     * Determine if address currency should be updated.
     */
    private function shouldUpdateAddressCurrency(Payment $payment): bool
    {
        return $payment->isSuccessful()
            && $payment->user_id !== null
            && filled($payment->currency);
    }
}
