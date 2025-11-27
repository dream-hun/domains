<?php

declare(strict_types=1);

namespace App\Services\Domain\Testing;

use App\Models\Contact;
use App\Services\Domain\DomainRegistrationServiceInterface;

/**
 * Fake Namecheap Domain Service for testing purposes.
 * This service provides predictable responses without connecting to the actual Namecheap API.
 */
final class FakeNamecheapDomainService implements DomainRegistrationServiceInterface
{
    /** @var array<string, array{available: bool, reason: string}> */
    private array $domainAvailability = [];

    /** @var array<string, array<string, mixed>> */
    private array $registeredDomains = [];

    /** @var array<string, array<string, mixed>> */
    private array $contacts = [];

    public function setDomainAvailability(string $domain, bool $available, string $reason = ''): self
    {
        $this->domainAvailability[$domain] = [
            'available' => $available,
            'reason' => $reason,
        ];

        return $this;
    }

    public function checkAvailability(array $domains): array
    {
        $results = [];

        foreach ($domains as $domain) {
            if (isset($this->domainAvailability[$domain])) {
                $results[$domain] = $this->domainAvailability[$domain];
            } else {
                // Default: domain is available
                $results[$domain] = [
                    'available' => true,
                    'reason' => 'Domain is available for registration',
                ];
            }
        }

        return $results;
    }

    public function suggestDomains(string $domain): array
    {
        return [
            [
                'domain' => 'suggested-'.$domain.'.com',
                'available' => true,
                'price' => '12.99 USD',
                'type' => 'international',
            ],
        ];
    }

    public function getDomainInfo(string $domain): array
    {
        if (isset($this->registeredDomains[$domain])) {
            return array_merge(['success' => true], $this->registeredDomains[$domain]);
        }

        return [
            'success' => true,
            'domain' => $domain,
            'status' => ['active'],
            'registrant' => 'TEST-REGISTRANT',
            'created_date' => now()->subYear()->toDateString(),
            'expiry_date' => now()->addYear()->toDateString(),
            'locked' => true,
            'auto_renew' => false,
        ];
    }

    public function registerDomain(string $domain, array $contactInfo, int $years): array
    {
        $this->registeredDomains[$domain] = [
            'domain' => $domain,
            'status' => ['active'],
            'contacts' => $contactInfo,
            'years' => $years,
            'created_date' => now()->toDateString(),
            'expiry_date' => now()->addYears($years)->toDateString(),
            'locked' => true,
            'auto_renew' => false,
        ];

        return [
            'success' => true,
            'domain' => $domain,
            'message' => 'Domain registered successfully (fake)',
        ];
    }

    public function renewDomainRegistration(string $domain, int $years): array
    {
        $newExpiry = now()->addYears($years)->toDateString();

        if (isset($this->registeredDomains[$domain])) {
            $this->registeredDomains[$domain]['expiry_date'] = $newExpiry;
        }

        return [
            'success' => true,
            'domain' => $domain,
            'expiry_date' => $newExpiry,
            'message' => 'Domain renewed successfully (fake)',
        ];
    }

    public function transferDomainRegistration(string $domain, string $authCode, array $contactInfo): array
    {
        return [
            'success' => true,
            'domain' => $domain,
            'message' => 'Domain transfer initiated successfully (fake)',
        ];
    }

    public function createContact(array $contactData): Contact
    {
        return Contact::factory()->make($contactData);
    }

    public function createContacts(array $contactData): array
    {
        $contactId = $contactData['contact_id'] ?? 'FAKE-'.uniqid();
        $this->contacts[$contactId] = $contactData;

        return [
            'contact_id' => $contactId,
            'auth' => 'fake-auth-'.uniqid(),
            'code' => 1000,
            'message' => 'Contact created successfully (fake)',
        ];
    }

    public function getDomainPricing(string $domain): array
    {
        return [
            'success' => true,
            'price' => 12.99,
            'currency' => 'USD',
            'message' => 'Pricing information retrieved successfully (fake)',
        ];
    }

    public function getDomainList(int $page = 1, int $pageSize = 20): array
    {
        return [
            'success' => true,
            'domains' => array_values($this->registeredDomains),
            'total' => count($this->registeredDomains),
            'message' => 'Domain list retrieved successfully (fake)',
        ];
    }

    public function updateNameservers(string $domain, array $nameservers): array
    {
        if (isset($this->registeredDomains[$domain])) {
            $this->registeredDomains[$domain]['nameservers'] = $nameservers;
        }

        return [
            'success' => true,
            'message' => 'Nameservers updated successfully (fake)',
        ];
    }

    public function getNameservers(string $domain): array
    {
        $nameservers = $this->registeredDomains[$domain]['nameservers'] ?? ['dns1.registrar-servers.com', 'dns2.registrar-servers.com'];

        return [
            'success' => true,
            'nameservers' => $nameservers,
            'message' => 'Nameservers retrieved successfully (fake)',
        ];
    }

    public function setDomainLock(string $domain, bool $lock): array
    {
        if (isset($this->registeredDomains[$domain])) {
            $this->registeredDomains[$domain]['locked'] = $lock;
        }

        return [
            'success' => true,
            'message' => $lock ? 'Domain locked successfully (fake)' : 'Domain unlocked successfully (fake)',
        ];
    }

    public function setAutoRenew(string $domain, bool $autoRenew): array
    {
        if (isset($this->registeredDomains[$domain])) {
            $this->registeredDomains[$domain]['auto_renew'] = $autoRenew;
        }

        return [
            'success' => true,
            'message' => $autoRenew ? 'Auto-renewal enabled (fake)' : 'Auto-renewal disabled (fake)',
        ];
    }

    public function reActivateDomain(string $domain): array
    {
        return [
            'success' => true,
            'domain' => $domain,
            'message' => 'Domain reactivated successfully (fake)',
        ];
    }

    public function updateDomainContacts(string $domain, array $contactInfo): array
    {
        if (isset($this->registeredDomains[$domain])) {
            $this->registeredDomains[$domain]['contacts'] = array_merge(
                $this->registeredDomains[$domain]['contacts'] ?? [],
                $contactInfo
            );
        }

        return [
            'success' => true,
            'message' => 'Domain contacts updated successfully (fake)',
        ];
    }
}
