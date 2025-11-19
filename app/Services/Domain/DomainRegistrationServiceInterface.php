<?php

declare(strict_types=1);

namespace App\Services\Domain;

interface DomainRegistrationServiceInterface extends DomainServiceInterface
{
    /**
     * Create contacts in the registry
     */
    public function createContacts(array $contactData): array;
}
