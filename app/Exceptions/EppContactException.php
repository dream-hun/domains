<?php

declare(strict_types=1);

namespace App\Exceptions;

use Throwable;

/**
 * Exception thrown for contact-specific EPP errors
 */
final class EppContactException extends EppException
{
    public function __construct(
        string $message = 'EPP contact operation failed',
        int $eppCode = 0,
        array $context = [],
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $eppCode, $context, $previous);
    }

    /**
     * Create exception for contact already exists
     */
    public static function contactExists(string $contactId): self
    {
        return new self(
            "Contact '$contactId' already exists",
            2302,
            ['contact_id' => $contactId]
        );
    }

    /**
     * Create exception for contact not found
     */
    public static function contactNotFound(string $contactId): self
    {
        return new self(
            "Contact '$contactId' does not exist",
            2303,
            ['contact_id' => $contactId]
        );
    }

    /**
     * Create exception for invalid contact data
     */
    public static function invalidContactData(string $field, string $reason = ''): self
    {
        $message = "Invalid contact data for field '$field'";
        if ($reason !== '' && $reason !== '0') {
            $message .= ": $reason";
        }

        return new self(
            $message,
            2005,
            ['field' => $field, 'reason' => $reason]
        );
    }

    /**
     * Create exception for contact association prohibits operation
     */
    public static function associationProhibitsOperation(string $contactId, string $operation): self
    {
        return new self(
            "Contact '$contactId' association prohibits $operation operation",
            2305,
            ['contact_id' => $contactId, 'operation' => $operation]
        );
    }
}
