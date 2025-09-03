<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Contact;
use App\Models\Domain;
use App\Models\DomainPrice;
use App\Models\Nameserver;
use App\Services\Domain\DomainRegistrationServiceInterface;
use App\Services\Domain\EppDomainService;
use App\Services\Domain\NamecheapDomainService;
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
            // Prepare contacts for the specific domain service
            $serviceContacts = $this->ensureContactsInEppRegistry($domainService, $processedContacts);

            $result = $domainService->registerDomain($domainName, $serviceContacts, $years);

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
     * Prepare contacts for the specific domain service
     *
     * @throws Exception
     */
    private function ensureContactsInEppRegistry(DomainRegistrationServiceInterface $domainService, array $contacts): array
    {
        // For EPP service, ensure contacts exist in EPP registry and return contact_ids
        if ($domainService instanceof EppDomainService) {
            return $this->prepareEppContacts($contacts);
        }

        // For Namecheap service, return full contact data arrays
        if ($domainService instanceof NamecheapDomainService) {
            return $this->prepareNamecheapContacts($contacts);
        }

        // Default: return contacts as-is
        return $contacts;
    }

    /**
     * Prepare contacts for EPP service
     *
     * @throws Exception
     */
    private function prepareEppContacts(array $contacts): array
    {
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
     * Prepare contacts for Namecheap service
     *
     * @throws Exception
     */
    private function prepareNamecheapContacts(array $contacts): array
    {
        $preparedContacts = [];

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

            // Convert contact model to array format expected by Namecheap
            $preparedContacts[$type] = [
                'first_name' => $contact->first_name,
                'last_name' => $contact->last_name,
                'organization' => $contact->organization,
                'address_one' => $contact->address_one,
                'address_two' => $contact->address_two,
                'city' => $contact->city,
                'state_province' => $contact->state_province,
                'postal_code' => $contact->postal_code,
                'country_code' => $contact->country_code,
                'phone' => $contact->phone,
                'email' => $contact->email,
            ];
        }

        return $preparedContacts;
    }

    /**
     * Create domain record in local database
     *
     * @throws Exception
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
            'is_locked' => true,
            'provider' => $service,
        ]);

        // Attach existing contacts to domain
        foreach ($contacts as $type => $contactData) {
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
            // Use default nameservers from config
            $defaultNameservers = config('default-nameservers.default_nameservers', [
                'ns1.example.com',
                'ns2.example.com',
            ]);

            foreach ($defaultNameservers as $index => $nameserver) {
                Nameserver::create([
                    'uuid' => (string) Str::uuid(),
                    'domain_id' => $domain->id,
                    'name' => $nameserver,
                    'type' => 'default',
                    'priority' => $index + 1,
                    'status' => 'active',
                ]);
            }

            return;
        }

        // Process custom nameservers
        foreach ($nameservers as $index => $nameserver) {
            if (in_array(mb_trim($nameserver), ['', '0'], true)) {
                continue;
            }

            // Create custom nameserver
            Nameserver::create([
                'uuid' => (string) Str::uuid(),
                'domain_id' => $domain->id,
                'name' => mb_trim($nameserver),
                'type' => 'custom',
                'priority' => $index + 1,
                'status' => 'active',
            ]);
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
