<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Contact;
use App\Models\Domain;
use App\Models\Nameserver;
use App\Models\Tld;
use App\Notifications\DomainRegisteredNotification;
use App\Services\Domain\DomainRegistrationServiceInterface;
use App\Services\Domain\EppDomainService;
use App\Services\Domain\NamecheapDomainService;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

readonly class RegisterDomainAction
{
    private DomainRegistrationServiceInterface $eppDomainService;

    private DomainRegistrationServiceInterface $namecheapDomainService;

    public function __construct()
    {
        $this->eppDomainService = resolve('epp_domain_service');
        $this->namecheapDomainService = resolve('namecheap_domain_service');
    }

    /**
     * Register a domain using the appropriate service based on TLD
     */
    public function handle(string $domainName, array $contactInfo, int $years, array $nameservers = [], bool $useSingleContact = false, ?int $userId = null): array
    {
        try {
            if ($userId === null && ! auth()->check()) {
                return [
                    'success' => false,
                    'message' => 'Cannot create domain: No user ID provided and no authenticated user found.',
                    'service' => $this->inferServiceName($domainName),
                ];
            }

            $serviceName = null;
            $domainService = $this->getDomainService($domainName);
            $serviceName = $domainService === $this->eppDomainService ? 'EPP' : 'Namecheap';

            Log::info(sprintf('Starting domain registration with %s service', $serviceName), [
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
                // Use original processed contacts (with database IDs) for domain record creation
                $domain = $this->createDomainRecord($domainName, $years, $processedContacts, $userId);
                $this->processNameservers($domain, $nameservers);
                $nsToSet = $domain->nameservers->pluck('name')->toArray();
                $updateResult = $domainService->updateNameservers($domainName, $nsToSet);
                if (! $updateResult['success']) {
                    Log::warning('Failed to update nameservers after registration', [
                        'domain' => $domainName,
                        'message' => $updateResult['message'] ?? 'Unknown error',
                    ]);
                }

                // Send notification to the domain owner
                $domain->owner->notify(new DomainRegisteredNotification($domain, $years));

                Log::info('Domain registered successfully with '.$serviceName, [
                    'domain' => $domainName,
                    'domain_id' => $domain->id,
                    'service' => $serviceName,
                ]);

                return [
                    'success' => true,
                    'domain' => $domainName,
                    'domain_id' => $domain->id,
                    'service' => $serviceName,
                    'message' => sprintf('Domain %s has been successfully registered using %s!', $domainName, $serviceName),
                ];
            }

            $errorMessage = $result['message'] ?? 'Domain registration failed';
            if (str_contains(mb_strtolower($errorMessage), 'not available') || str_contains(mb_strtolower($errorMessage), 'already registered')) {
                $errorMessage = sprintf('The domain %s is no longer available. It may have been registered by someone else while you were completing your order.', $domainName);
            }

            return [
                'success' => false,
                'message' => $errorMessage,
                'service' => $serviceName,
            ];

        } catch (Exception $exception) {
            Log::error('Domain registration action failed', [
                'domain' => $domainName,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            $errorMessage = $exception->getMessage();
            if (str_contains(mb_strtolower($errorMessage), 'not available') || str_contains(mb_strtolower($errorMessage), 'already registered')) {
                $errorMessage = sprintf('The domain %s is no longer available. It may have been registered by someone else while you were completing your order.', $domainName);
            }

            return [
                'success' => false,
                'message' => 'An error occurred during domain registration: '.$errorMessage,
                'service' => $serviceName,
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

    private function inferServiceName(string $domainName): string
    {
        return mb_strtolower($this->extractTld($domainName)) === '.rw' ? 'EPP' : 'Namecheap';
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
            [$contactId, $contact] = $this->extractContactIDFromTheContactData($contactData, $type);

            // Check if contact has an EPP contact_id
            if (! $contact->contact_id) {
                // Contact doesn't exist in EPP registry, create it
                Log::info('Creating contact in EPP registry', [
                    'type' => $type,
                    'database_id' => $contactId,
                    'contact_name' => $contact->full_name,
                ]);

                $eppContactId = $this->createContactInEppRegistry($contact);

                // Update the contact record with the EPP contact_id
                $contact->update(['contact_id' => $eppContactId]);

                $ensuredContacts[$type] = $eppContactId;
            } else {
                // Contact exists in EPP registry, use its contact_id for EPP registration
                $ensuredContacts[$type] = $contact->contact_id;
            }

            Log::info('Using EPP contact for domain registration', [
                'type' => $type,
                'database_id' => $contactId,
                'epp_contact_id' => $ensuredContacts[$type],
                'contact_name' => $contact->full_name,
            ]);
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
            [$contactId, $contact] = $this->extractContactIDFromTheContactData($contactData, $type);

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
     * @throws Exception|Throwable
     */
    private function createDomainRecord(string $domainName, int $years, array $contacts, ?int $userId = null): Domain
    {
        $tldString = $this->extractTld($domainName);
        $tld = Tld::query()
            ->with(['currentTldPricings' => fn ($q) => $q->with('currency')])
            ->where('name', $tldString)
            ->firstOrFail();

        $tldPricing = $tld->currentTldPricings->first();
        throw_if($tldPricing === null, Exception::class, sprintf('No current pricing found for TLD: %s', $tldString));

        $ownerId = $userId ?? auth()->id();

        throw_if($ownerId === null, Exception::class, 'Cannot create domain: No user ID provided and no authenticated user found.');

        $domain = Domain::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => $domainName,
            'owner_id' => $ownerId,
            'years' => $years,
            'registered_at' => now(),
            'expires_at' => now()->addYears($years),
            'auto_renew' => false,
            'status' => 'active',
            'tld_pricing_id' => $tldPricing->id,
            'is_premium' => false,
            'is_locked' => true,
        ]);

        foreach ($contacts as $type => $contactData) {
            $contactId = is_array($contactData) ? ($contactData['id'] ?? null) : $contactData;
            if (empty($contactId)) {
                throw new Exception(sprintf('Missing or invalid contact ID for type: %s', $type));
            }

            $contactId = (int) $contactId;

            $domain->contacts()->attach($contactId, [
                'type' => $type,
                'user_id' => $ownerId,
            ]);
        }

        return $domain;
    }

    /**
     * Process nameservers for the domain
     */
    private function processNameservers(Domain $domain, array $nameservers): void
    {
        $hasCustomNameservers = $nameservers !== [];
        $sourceNameservers = $hasCustomNameservers
            ? $nameservers
            : config('default-nameservers.nameservers', [
                'ns1.example.com',
                'ns2.example.com',
            ]);

        $normalized = collect($sourceNameservers)
            ->map(fn ($nameserver): string => mb_strtolower(mb_trim((string) $nameserver)))
            ->filter(fn (string $nameserver): bool => $nameserver !== '' && $nameserver !== '0')
            ->unique()
            ->values();

        if ($normalized->isEmpty()) {
            return;
        }

        $type = $hasCustomNameservers ? 'custom' : 'default';

        $nameserverIds = $normalized->map(function (string $nameserver, int $index) use ($type): int {
            $record = Nameserver::query()->firstOrCreate(
                ['name' => $nameserver],
                [
                    'uuid' => (string) Str::uuid(),
                    'type' => $type,
                    'priority' => $index + 1,
                    'status' => 'active',
                ]
            );

            if (! $record->wasRecentlyCreated) {
                $record->update([
                    'type' => $type,
                    'priority' => $index + 1,
                    'status' => 'active',
                ]);
            }

            return $record->id;
        })->all();

        $domain->nameservers()->sync($nameserverIds);
    }

    /**
     * Extract TLD from domain name
     */
    private function extractTld(string $domainName): string
    {
        $parts = explode('.', $domainName);

        return '.'.end($parts);
    }

    /**
     * Create a contact in the EPP registry
     *
     * @throws Exception
     */
    private function createContactInEppRegistry(Contact $contact): string
    {
        // Validate required fields for EPP contact creation
        $requiredFields = [
            'full_name' => 'Full name',
            'address_one' => 'Address',
            'city' => 'City',
            'country_code' => 'Country code',
            'phone' => 'Phone number',
            'email' => 'Email address',
        ];

        foreach ($requiredFields as $field => $label) {
            if (empty($contact->$field)) {
                throw new Exception(sprintf("Contact '%s' is missing required field: %s. Please update the contact information before registering the domain.", $contact->full_name, $label));
            }
        }

        // Generate a unique EPP contact ID
        $eppContactId = 'C'.$contact->id.'-'.time();

        // Prepare contact data for EPP
        $eppContactData = [
            'contact_id' => $eppContactId,
            'name' => $contact->full_name,
            'organization' => $contact->organization,
            'street1' => $contact->address_one,
            'street2' => $contact->address_two,
            'city' => $contact->city,
            'province' => $contact->state_province,
            'postal_code' => $contact->postal_code,
            'country_code' => $contact->country_code,
            'voice' => $contact->phone,
            'email' => $contact->email,
        ];

        // Create contact in EPP registry using the EPP service
        $result = $this->eppDomainService->createContacts($eppContactData);

        if (empty($result['contact_id'])) {
            throw new Exception('Failed to create contact in EPP registry: '.($result['message'] ?? 'Unknown error'));
        }

        Log::info('Contact created successfully in EPP registry', [
            'database_id' => $contact->id,
            'epp_contact_id' => $result['contact_id'],
            'contact_name' => $contact->full_name,
        ]);

        return $result['contact_id'];
    }

    /**
     * @throws Exception
     */
    private function extractContactIDFromTheContactData(mixed $contactData, int|string $type): array
    {
        $contactId = is_array($contactData) ? ($contactData['id'] ?? null) : $contactData;

        // Ensure contact ID is valid and not empty
        if (empty($contactId)) {
            throw new Exception(sprintf('Missing or invalid contact ID for type: %s', $type));
        }

        // Cast to integer to ensure proper type
        $contactId = (int) $contactId;

        // Get the contact from the database
        $contact = Contact::query()->find($contactId);
        if (! $contact) {
            throw new Exception(sprintf('Contact with ID %d not found for type: %s', $contactId, $type));
        }

        return [$contactId, $contact];
    }
}
