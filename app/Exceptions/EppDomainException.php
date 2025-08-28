<?php

declare(strict_types=1);

namespace App\Exceptions;

use Throwable;

/**
 * Exception thrown for domain-specific EPP errors
 */
final class EppDomainException extends EppException
{
    public function __construct(
        string $message = 'EPP domain operation failed',
        int $eppCode = 0,
        array $context = [],
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $eppCode, $context, $previous);
    }

    /**
     * Create exception for domain already exists
     */
    public static function domainExists(string $domain): self
    {
        return new self(
            "Domain '$domain' already exists",
            2302,
            ['domain' => $domain]
        );
    }

    /**
     * Create exception for domain not found
     */
    public static function domainNotFound(string $domain): self
    {
        return new self(
            "Domain '$domain' does not exist",
            2303,
            ['domain' => $domain]
        );
    }

    /**
     * Create exception for domain status prohibits operation
     */
    public static function statusProhibitsOperation(string $domain, string $operation): self
    {
        return new self(
            "Domain '$domain' status prohibits $operation operation",
            2304,
            ['domain' => $domain, 'operation' => $operation]
        );
    }

    /**
     * Create exception for invalid authorization info
     */
    public static function invalidAuthInfo(string $domain): self
    {
        return new self(
            "Invalid authorization information for domain '$domain'",
            2202,
            ['domain' => $domain]
        );
    }

    /**
     * Create exception for domain not eligible for transfer
     */
    public static function notEligibleForTransfer(string $domain): self
    {
        return new self(
            "Domain '$domain' is not eligible for transfer",
            2106,
            ['domain' => $domain]
        );
    }

    /**
     * Create exception for domain pending transfer
     */
    public static function pendingTransfer(string $domain): self
    {
        return new self(
            "Domain '$domain' has a pending transfer",
            2300,
            ['domain' => $domain]
        );
    }
}
