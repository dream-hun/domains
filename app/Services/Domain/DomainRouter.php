<?php

declare(strict_types=1);

namespace App\Services\Domain;

/**
 * Centralises the decision of which registrar service to use for a given domain.
 * Previously this logic was duplicated (inconsistently) in RegisterDomainAction,
 * RenewDomainAction, and Actions/Domains/RenewDomainAction.
 */
final readonly class DomainRouter
{
    public function __construct(
        private DomainRegistrationServiceInterface $eppService,
        private DomainRegistrationServiceInterface $namecheapService,
    ) {}

    public function resolveForDomain(string $domainName): DomainRegistrationServiceInterface
    {
        return $this->isLocalTld($domainName) ? $this->eppService : $this->namecheapService;
    }

    public function isLocalTld(string $domainName): bool
    {
        return str_ends_with(mb_strtolower($domainName), '.rw');
    }

    public function extractTld(string $domainName): string
    {
        $parts = explode('.', $domainName);

        return '.'.end($parts);
    }

    public function serviceName(string $domainName): string
    {
        return $this->isLocalTld($domainName) ? 'EPP' : 'Namecheap';
    }

    public function eppService(): DomainRegistrationServiceInterface
    {
        return $this->eppService;
    }

    public function namecheapService(): DomainRegistrationServiceInterface
    {
        return $this->namecheapService;
    }
}
