<?php

declare(strict_types=1);

namespace App\Exceptions;

use Throwable;

/**
 * Exception thrown when EPP authentication fails
 */
final class EppAuthenticationException extends EppException
{
    public function __construct(
        string $message = 'EPP authentication failed',
        int $eppCode = 2200,
        array $context = [],
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $eppCode, $context, $previous);
    }
}
