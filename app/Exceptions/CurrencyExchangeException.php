<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

final class CurrencyExchangeException extends Exception
{
    public static function unsupportedCurrency(string $currency): self
    {
        return new self(sprintf("Currency code '%s' is not supported. Only USD and RWF are supported.", $currency));
    }

    public static function invalidAmount(float $amount): self
    {
        return new self('Amount must be positive, got: '.$amount);
    }

    public static function invalidApiKey(): self
    {
        return new self('Exchange rate API key is invalid or missing.');
    }

    public static function quotaReached(): self
    {
        return new self('Exchange rate API quota has been reached.');
    }

    public static function malformedRequest(string $details = ''): self
    {
        $message = 'The exchange rate API request was malformed.';
        if ($details !== '') {
            $message .= ' Details: '.$details;
        }

        return new self($message);
    }

    public static function inactiveAccount(): self
    {
        return new self('Exchange rate API account is inactive. Please verify your email address.');
    }

    public static function apiError(string $errorType, string $details = ''): self
    {
        $message = 'Exchange rate API error: '.$errorType;
        if ($details !== '') {
            $message .= '. Details: '.$details;
        }

        return new self($message);
    }

    public static function networkError(string $details): self
    {
        return new self('Network error while fetching exchange rates: '.$details);
    }

    public static function unexpectedResponse(string $details = ''): self
    {
        $message = 'Unexpected response from exchange rate API.';
        if ($details !== '') {
            $message .= ' Details: '.$details;
        }

        return new self($message);
    }
}
