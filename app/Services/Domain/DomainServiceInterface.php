<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Models\Contact;

interface DomainServiceInterface
{
    /**
     * Check if a domain is available for registration
     *
     * @param  array  $domain  The domain name to check
     * @return array<string, array{available: bool, reason: string, is_premium?: bool, premium_price?: float|null, eap_fee?: float}>
     */
    public function checkAvailability(array $domain): array;

    /**
     * Suggest other available domains to the user
     */
    public function suggestDomains(string $domain): array;

    /**
     * Get information about a domain
     *
     * @param  string  $domain  The domain name to get information for
     * @return array{success: bool, domain?: string, status?: array<string>, registrant?: string, created_date?: string, expiry_date?: string, message?: string, locked?: bool, auto_renew?: bool}
     */
    public function getDomainInfo(string $domain): array;

    /**
     * Register a new domain
     *
     * @param  string  $domain  The domain name to register
     * @param  array<string, mixed>  $contactInfo  Contact information for the domain
     * @param  int  $years  Number of years to register the domain for
     * @return array{success: bool, domain?: string, message?: string}
     */
    public function registerDomain(string $domain, array $contactInfo, int $years): array;

    /**
     * Renew a domain registration
     *
     * @param  string  $domain  The domain name to renew
     * @param  int  $years  Number of years to renew the domain for
     * @return array{success: bool, domain?: string, expiry_date?: string, message?: string}
     */
    public function renewDomainRegistration(string $domain, int $years): array;

    /**
     * Transfer a domain from another registrar
     *
     * @param  string  $domain  The domain name to transfer
     * @param  string  $authCode  Authorization code for the transfer
     * @param  array<string, mixed>  $contactInfo  Contact information for the domain
     * @return array{success: bool, domain?: string, message?: string}
     */
    public function transferDomainRegistration(string $domain, string $authCode, array $contactInfo): array;

    /**
     * Create a contact
     *
     * @param  array<string, mixed>  $contactData  Contact information
     * @return Contact The created contact
     */
    public function createContact(array $contactData): Contact;

    /**
     * Get domain pricing information
     *
     * @param  string  $domain  The domain name to get pricing for
     * @return array{success: bool, price?: float, currency?: string, message?: string}
     */
    public function getDomainPricing(string $domain): array;

    /**
     * Get domain list for a user
     *
     * @param  int  $page  Page number
     * @param  int  $pageSize  Number of domains per page
     * @return array{success: bool, domains?: array, total?: int, message?: string}
     */
    public function getDomainList(int $page = 1, int $pageSize = 20): array;

    /**
     * Update domain nameservers
     *
     * @param  string  $domain  The domain name
     * @param  array  $nameservers  Array of nameserver addresses
     * @return array{success: bool, message?: string}
     */
    public function updateNameservers(string $domain, array $nameservers): array;

    /**
     * Get domain nameservers
     *
     * @param  string  $domain  The domain name
     * @return array{success: bool, nameservers?: array, message?: string}
     */
    public function getNameservers(string $domain): array;

    /**
     * Set domain lock status
     *
     * @param  string  $domain  The domain name
     * @param  bool  $lock  True to lock, false to unlock
     * @return array{success: bool, message?: string}
     */
    public function setDomainLock(string $domain, bool $lock): array;

    public function setAutoRenew(string $domain, bool $autoRenew): array;

    public function reActivateDomain(string $domain): array;

    /**
     * Update domain contacts
     *
     * @param  string  $domain  The domain name
     * @param  array  $contactInfo  Contact information for the domain
     * @return array{success: bool, message?: string}
     */
    public function updateDomainContacts(string $domain, array $contactInfo): array;
}
