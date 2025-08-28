<?php

declare(strict_types=1);

namespace App\Exceptions;

use Throwable;

/**
 * Exception thrown when EPP connection fails
 */
final class EppConnectionException extends EppException
{
    public function __construct(
        string $message = 'EPP connection failed',
        int $eppCode = 0,
        array $context = [],
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $eppCode, $context, $previous);
    }
}
