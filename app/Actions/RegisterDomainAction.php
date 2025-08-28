<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Contact;
use App\Models\Domain;
use App\Models\DomainPrice;
use App\Models\Nameserver;
use App\Services\Domain\DomainRegistrationServiceInterface;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final readonly class RegisterDomainAction
{
    private DomainRegistrationServiceInterface $eppDomainService;

    private DomainRegistrationServiceInterface $namecheapDomainService;

    public function __construct()
    {
        $this->eppDomainService = app('epp_domain_service');
        $this->namecheapDomainService = app('namecheap_domain_service');
    }

    /**
     * Register a domain using the appropriate service based on TLD
     */
    public function handle(string $domainName, array $contactInfo, int $years, array $nameservers = [], bool $useSingleContact = false): array
    {
        try {
            $domainService = $this->getDomainService($domainName);
            $serviceName = $domainService === $this->eppDomainService ? 'EPP' : 'Namecheap';

            Log::info("Starting domain registration with $serviceName service", [
                'domain' => $domainName,
                'years' => $years,
                'service' => $serviceName,
                'use_single_contact' => $useSingleContact,
            ]);

            $processedContacts = $this->processContactsForService($contactInfo, $useSingleContact);
            // Ensure all contacts exist in the EPP registry before domain registration
            $processedContacts = $this->ensureContactsInEppRegistry($domainService, $processedContacts);

            $result = $domainService->registerDomain($domainName, $processedContacts, $years);

            if ($result['success']) {
                $domain = $this->createDomainRecord($domainName, $years, $processedContacts, $serviceName);
                $this->processNameservers($domain, $nameservers);
                $nsToSet = $domain->nameservers->pluck('name')->toArray();
                $updateResult = $domainService->updateNameservers($domainName, $nsToSet);
                if (! $updateResult['success']) {
                    Log::warning('Failed to update nameservers after registration', [
                        'domain' => $domainName,
                        'message' => $updateResult['message'] ?? 'Unknown error',
                    ]);
                }

                Cart::clear();

                Log::info("Domain registered successfully with $serviceName", [
                    'domain' => $domainName,
                    'domain_id' => $domain->id,
                    'service' => $serviceName,
                ]);

                return [
                    'success' => true,
                    'domain' => $domainName,
                    'domain_id' => $domain->id,
                    'service' => $serviceName,
                    'message' => "Domain $domainName has been successfully registered using $serviceName!",
                ];
            }

            $errorMessage = $result['message'] ?? 'Domain registration failed';
            if (str_contains(mb_strtolower($errorMessage), 'not available') || str_contains(mb_strtolower($errorMessage), 'already registered')) {
                $errorMessage = "The domain $domainName is no longer available. It may have been registered by someone else while you were completing your order.";
            }

            return [
                'success' => false,
                'message' => $errorMessage,
                'service' => $serviceName,
            ];

        } catch (Exception $e) {
            Log::error('Domain registration action failed', [
                'domain' => $domainName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $errorMessage = $e->getMessage();
            if (str_contains(mb_strtolower($errorMessage), 'not available') || str_contains(mb_strtolower($errorMessage), 'already registered')) {
                $errorMessage = "The domain $domainName is no longer available. It may have been registered by someone else while you were completing your order.";
            }

            return [
                'success' => false,
                'message' => 'An error occurred during domain registration: '.$errorMessage,
            ];
        }
    }

    /**
     * Determine which domain service to use based on TLD
     */
    private function getDomainService(string $domainName): DomainRegistrationServiceInterface
    {
        $tld = $this->extractTld($domainName);
        if (mb_strtolower($tld) === '.rw') {
            return $this->eppDomainService;
        }

        return $this->namecheapDomainService;
    }

    /**
     * Process contacts for the specific service
     */
    private function processContactsForService(
        array $contactInfo,
        bool $useSingleContact
    ): array {
        $processedContacts = [];

        if ($useSingleContact && isset($contactInfo['registrant'])) {
            $registrantContact = $contactInfo['registrant'];
            foreach (['registrant', 'admin', 'technical', 'billing'] as $type) {
                $processedContacts[$type] = $registrantContact;
            }
        } else {
            foreach (['registrant', 'admin', 'technical', 'billing'] as $type) {
                if (isset($contactInfo[$type])) {
                    $processedContacts[$type] = $contactInfo[$type];
                } else {
                    $processedContacts[$type] = $processedContacts['registrant'] ??
                        $processedContacts['admin'] ??
                        $processedContacts['technical'] ??
                        $processedContacts['billing'];
                }
            }
        }

        return $processedContacts;
    }

    /**
     * Ensure all contacts exist in the EPP registry before domain registration
     */
    private function ensureContactsInEppRegistry(DomainRegistrationServiceInterface $domainService, array $contacts): array
    {
        // If this is not an EPP service, return contacts as-is
        if (! $domainService instanceof \App\Services\Domain\EppDomainService) {
            return $contacts;
        }

        $ensuredContacts = [];

        foreach ($contacts as $type => $contactData) {
            // Extract contact ID from the contact data
            $contactId = is_array($contactData) ? ($contactData['id'] ?? null) : $contactData;

            if (! $contactId) {
                throw new Exception("Missing contact ID for type: $type");
            }

            // Get the contact from the database
            $contact = Contact::find($contactId);
            if (! $contact) {
                throw new Exception("Contact with ID $contactId not found");
            }

            // Check if contact has an EPP contact_id
            if (! $contact->contact_id) {
                throw new Exception("Contact '$contact->full_name' (ID: $contactId) does not exist in the EPP registry. Please create this contact first before registering the domain.");
            }

            // Contact exists in EPP registry, use its contact_id
            $ensuredContacts[$type] = $contact->contact_id;
        }

        return $ensuredContacts;
    }

    /**
     * Create domain record in local database
     */
    private function createDomainRecord(string $domainName, int $years, array $contacts, string $service): Domain
    {
        // Get the domain price for the TLD
        $tld = $this->extractTld($domainName);
        $domainPrice = DomainPrice::where('tld', $tld)->firstOrFail();

        $domain = Domain::create([
            'uuid' => (string) Str::uuid(),
            'name' => $domainName,
            'owner_id' => auth()->id(),
            'years' => $years,
            'registered_at' => now(),
            'expires_at' => now()->addYears($years),
            'auto_renew' => false,
            'status' => 'active',
            'domain_price_id' => $domainPrice->id,
            'is_premium' => false,
            'is_locked' => false,
            'provider' => $service,
        ]);

        // Attach existing contacts to domain
        foreach ($contacts as $type => $contactData) {
            // Extract contact ID from the contact data
            // contactData can be either a contact ID (string) or an array with contact data
            $contactId = is_array($contactData) ? ($contactData['id'] ?? null) : $contactData;

            // Ensure we have a valid contact ID
            if (! $contactId) {
                throw new Exception("Missing contact ID for type: $type");
            }

            $domain->contacts()->attach($contactId, [
                'type' => $type,
                'user_id' => auth()->id(),
            ]);
        }

        return $domain;
    }

    /**
     * Process nameservers for the domain
     */
    private function processNameservers(Domain $domain, array $nameservers): void
    {
        if ($nameservers === []) {
            // Use default nameservers
            $defaultNameservers = Nameserver::where('type', 'default')
                ->where('status', 'active')
                ->orderBy('priority')
                ->get();

            foreach ($defaultNameservers as $nameserver) {
                $domain->nameservers()->attach($nameserver->id);
            }

            return;
        }

        // Process custom nameservers
        foreach ($nameservers as $index => $nameserver) {
            if (in_array(mb_trim($nameserver), ['', '0'], true)) {
                continue;
            }

            $existingNs = Nameserver::where('name', $nameserver)->first();

            if ($existingNs && $existingNs->type === 'default') {
                // Attach existing default nameserver
                $domain->nameservers()->attach($existingNs->id);
            } else {
                // Create new custom nameserver
                $ns = Nameserver::create([
                    'name' => $nameserver,
                    'type' => 'custom',
                    'priority' => $index + 1,
                    'status' => 'active',
                ]);
                $domain->nameservers()->attach($ns->id);
            }
        }
    }

    /**
     * Extract TLD from domain name
     */
    private function extractTld(string $domainName): string
    {
        $parts = explode('.', $domainName);

        return '.'.end($parts);
    }
}
