<?php

declare(strict_types=1);

namespace App\Services;

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
    public function validate(string $method): array
    {
        return match ($method) {
            'stripe' => $this->validateStripe(),
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
        return ! empty(config('services.payment.stripe.publishable_key'))
            && ! empty(config('services.payment.stripe.secret_key'));
    }
}
