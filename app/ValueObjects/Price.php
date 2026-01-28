<?php

declare(strict_types=1);

namespace App\ValueObjects;

use App\Services\PriceFormatter;
use InvalidArgumentException;

final readonly class Price
{
    public function __construct(
        private int $amountInMinorUnits,
        private string $currency
    ) {
        throw_if($amountInMinorUnits < 0, InvalidArgumentException::class, 'Price amount cannot be negative');

        $normalizedCurrency = $this->normalizeCurrency($currency);

        if (! preg_match('/^[A-Z]{3}$/', $normalizedCurrency)) {
            throw new InvalidArgumentException(sprintf('Invalid currency code: %s', $currency));
        }
    }

    /**
     * Create a Price from major units (e.g., dollars).
     */
    public static function fromMajorUnits(float $amount, string $currency): self
    {
        throw_if($amount < 0, InvalidArgumentException::class, 'Price amount cannot be negative');

        $minorUnits = (int) round($amount * 100);

        return new self($minorUnits, $currency);
    }

    /**
     * Create a Price from minor units (e.g., cents).
     */
    public static function fromMinorUnits(int $amount, string $currency): self
    {
        return new self($amount, $currency);
    }

    /**
     * Create a zero-value Price.
     */
    public static function zero(string $currency = 'USD'): self
    {
        return new self(0, $currency);
    }

    /**
     * Get the amount in minor units (e.g., cents).
     */
    public function getAmountInMinorUnits(): int
    {
        return $this->amountInMinorUnits;
    }

    /**
     * Get the amount in major units (e.g., dollars).
     */
    public function toMajorUnits(): float
    {
        return $this->amountInMinorUnits / 100;
    }

    /**
     * Get the currency code (normalized).
     */
    public function getCurrency(): string
    {
        return $this->normalizeCurrency($this->currency);
    }

    /**
     * Get the raw currency code as provided.
     */
    public function getRawCurrency(): string
    {
        return $this->currency;
    }

    /**
     * Format the price for display.
     */
    public function format(): string
    {
        return resolve(PriceFormatter::class)->formatPrice($this);
    }

    /**
     * Add another price to this one.
     *
     * @throws InvalidArgumentException If currencies don't match
     */
    public function add(self $other): self
    {
        $thisCurrency = $this->normalizeCurrency($this->currency);
        $otherCurrency = $other->getCurrency();

        if ($thisCurrency !== $otherCurrency) {
            throw new InvalidArgumentException(
                sprintf('Cannot add prices with different currencies: %s and %s', $thisCurrency, $otherCurrency)
            );
        }

        return new self($this->amountInMinorUnits + $other->getAmountInMinorUnits(), $thisCurrency);
    }

    /**
     * Subtract another price from this one.
     *
     * @throws InvalidArgumentException If currencies don't match or result would be negative
     */
    public function subtract(self $other): self
    {
        $thisCurrency = $this->normalizeCurrency($this->currency);
        $otherCurrency = $other->getCurrency();

        if ($thisCurrency !== $otherCurrency) {
            throw new InvalidArgumentException(
                sprintf('Cannot subtract prices with different currencies: %s and %s', $thisCurrency, $otherCurrency)
            );
        }

        $result = $this->amountInMinorUnits - $other->getAmountInMinorUnits();

        throw_if($result < 0, InvalidArgumentException::class, 'Subtraction would result in negative price');

        return new self($result, $thisCurrency);
    }

    /**
     * Multiply the price by a factor.
     */
    public function multiply(float $factor): self
    {
        throw_if($factor < 0, InvalidArgumentException::class, 'Factor cannot be negative');

        $newAmount = (int) round($this->amountInMinorUnits * $factor);

        return new self($newAmount, $this->normalizeCurrency($this->currency));
    }

    /**
     * Check if this price equals another.
     */
    public function equals(self $other): bool
    {
        return $this->amountInMinorUnits === $other->getAmountInMinorUnits()
            && $this->normalizeCurrency($this->currency) === $other->getCurrency();
    }

    /**
     * Check if this price is greater than another.
     *
     * @throws InvalidArgumentException If currencies don't match
     */
    public function isGreaterThan(self $other): bool
    {
        $this->assertSameCurrency($other);

        return $this->amountInMinorUnits > $other->getAmountInMinorUnits();
    }

    /**
     * Check if this price is less than another.
     *
     * @throws InvalidArgumentException If currencies don't match
     */
    public function isLessThan(self $other): bool
    {
        $this->assertSameCurrency($other);

        return $this->amountInMinorUnits < $other->getAmountInMinorUnits();
    }

    /**
     * Check if this price is zero.
     */
    public function isZero(): bool
    {
        return $this->amountInMinorUnits === 0;
    }

    /**
     * Normalize currency code (e.g., FRW -> RWF).
     */
    private function normalizeCurrency(string $currency): string
    {
        $currency = mb_strtoupper($currency);

        /** @var array<string, string> $aliases */
        $aliases = config('currency_exchange.aliases', []);

        return $aliases[$currency] ?? $currency;
    }

    /**
     * Assert that another price has the same currency.
     *
     * @throws InvalidArgumentException If currencies don't match
     */
    private function assertSameCurrency(self $other): void
    {
        $thisCurrency = $this->normalizeCurrency($this->currency);
        $otherCurrency = $other->getCurrency();

        if ($thisCurrency !== $otherCurrency) {
            throw new InvalidArgumentException(
                sprintf('Cannot compare prices with different currencies: %s and %s', $thisCurrency, $otherCurrency)
            );
        }
    }
}
