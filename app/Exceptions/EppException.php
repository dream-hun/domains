<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Throwable;

/**
 * Base EPP Exception class for handling EPP-specific errors
 */
final class EppException extends Exception
{
    private int $eppCode;

    private array $context;

    public function __construct(
        string $message = '',
        int $eppCode = 0,
        array $context = [],
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $eppCode, $previous);

        $this->eppCode = $eppCode;
        $this->context = $context;
    }

    public function getEppCode(): int
    {
        return $this->eppCode;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function setContext(array $context): void
    {
        $this->context = $context;
    }

    /**
     * Get a human-readable description of the EPP error code
     */
    public function getEppCodeDescription(): string
    {
        return match ($this->eppCode) {
            1000 => 'Command completed successfully',
            1001 => 'Command completed successfully; action pending',
            1300 => 'Command completed successfully; no messages',
            1301 => 'Command completed successfully; ack to dequeue',
            1500 => 'Command completed successfully; ending session',
            2000 => 'Unknown command',
            2001 => 'Command syntax error',
            2002 => 'Command use error',
            2003 => 'Required parameter missing',
            2004 => 'Parameter value range error',
            2005 => 'Parameter value syntax error',
            2100 => 'Unimplemented protocol version',
            2101 => 'Unimplemented command',
            2102 => 'Unimplemented option',
            2103 => 'Unimplemented extension',
            2104 => 'Billing failure',
            2105 => 'Object is not eligible for renewal',
            2106 => 'Object is not eligible for transfer',
            2200 => 'Authentication error',
            2201 => 'Authorization error',
            2202 => 'Invalid authorization information',
            2300 => 'Object pending transfer',
            2301 => 'Object not pending transfer',
            2302 => 'Object exists',
            2303 => 'Object does not exist',
            2304 => 'Object status prohibits operation',
            2305 => 'Object association prohibits operation',
            2306 => 'Parameter value policy error',
            2307 => 'Unimplemented object service',
            2308 => 'Data management policy violation',
            2400 => 'Command failed',
            2500 => 'Command failed; server closing connection',
            2501 => 'Authentication error; server closing connection',
            2502 => 'Session limit exceeded; server closing connection',
            default => 'Unknown EPP error code',
        };
    }

    /**
     * Check if this is a temporary error that might succeed on retry
     */
    public function isRetryable(): bool
    {
        return in_array($this->eppCode, [
            2400, // Command failed
            2500, // Command failed; server closing connection
        ]);
    }

    /**
     * Check if this is a client error (4xx equivalent)
     */
    public function isClientError(): bool
    {
        return $this->eppCode >= 2000 && $this->eppCode < 2400;
    }

    /**
     * Check if this is a server error (5xx equivalent)
     */
    public function isServerError(): bool
    {
        return $this->eppCode >= 2400;
    }

    /**
     * Get formatted error message with EPP code and description
     */
    public function getFormattedMessage(): string
    {
        return sprintf(
            '[EPP %d] %s: %s',
            $this->eppCode,
            $this->getEppCodeDescription(),
            $this->getMessage()
        );
    }
}
