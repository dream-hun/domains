<?php

declare(strict_types=1);

namespace App\Services\Domain;

interface DomainRegistrationServiceInterface
{
    /**
     * Register a domain
     */
    public function registerDomain(string $domain, array $contactInfo, int $years): array;

    /**
     * Create contacts in the registry
     */
    public function createContacts(array $contactData): array;
}
