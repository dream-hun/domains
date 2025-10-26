<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;

final class PaymentMethodValidator
{
    /**
     * Validate Stripe payment method
     */
    public function validateStripe(): array
    {
        if (! $this->isStripeConfigured()) {
            return [
                'valid' => false,
                'error' => 'Stripe payment is not configured. Please contact support.',
            ];
        }

        return [
            'valid' => true,
        ];
    }

    /**
     * Validate account credit payment method
     */
    public function validateAccountCredit(User $user, float $amount): array
    {
        if (! $user->hasAccountCredit($amount)) {
            return [
                'valid' => false,
                'error' => 'Insufficient account credit. Your balance is '.$user->account_credit.'. Please add funds or use a different payment method.',
            ];
        }

        return [
            'valid' => true,
        ];
    }

    /**
     * Validate PayPal payment method (placeholder)
     */
    public function validatePayPal(): array
    {
        return [
            'valid' => false,
            'error' => 'PayPal payment is not yet available.',
        ];
    }

    /**
     * Validate payment method based on type
     */
    public function validate(string $method, ?User $user = null, ?float $amount = null): array
    {
        return match ($method) {
            'stripe' => $this->validateStripe(),
            'account_credit' => $user && $amount ? $this->validateAccountCredit($user, $amount) : [
                'valid' => false,
                'error' => 'User and amount required for account credit validation',
            ],
            'paypal' => $this->validatePayPal(),
            default => [
                'valid' => false,
                'error' => 'Invalid payment method',
            ],
        };
    }

    /**
     * Check if Stripe is configured
     */
    private function isStripeConfigured(): bool
    {
        return ! empty(config('cashier.key')) && ! empty(config('cashier.secret'));
    }
}
