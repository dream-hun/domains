<?php

declare(strict_types=1);

namespace App\Actions\Domains;

use App\Models\Contact;
use App\Models\Domain;
use App\Services\Domain\DomainServiceInterface;
use Exception;
use Illuminate\Support\Facades\Log;

final readonly class UpdateDomainContactsAction
{
    public function __construct(
        private DomainServiceInterface $domainService
    ) {}

    public function handle(Domain $domain, array $contactData): array
    {
        try {
            // Check if we're dealing with new form format (individual fields) or old format (contact_id)
            if ($this->isNewFormFormat($contactData)) {
                return $this->handleNewFormFormat($domain, $contactData);
            }

            return $this->handleOldFormFormat($domain, $contactData);

        } catch (Exception $e) {
            Log::error('Failed to update domain contacts', [
                'domain' => $domain->name,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to update domain contacts: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Handle new form format with individual contact fields
     */
    private function handleNewFormFormat(Domain $domain, array $contactData): array
    {
        $contactType = $contactData['contact_type'];

        // Create or update contact with the provided data
        $contact = $this->createOrUpdateContact($contactData);

        // Get current domain contacts
        $currentContacts = $this->getCurrentDomainContacts($domain);

        // Update the specific contact type
        $currentContacts[$contactType] = ['contact_id' => $contact->id];

        // Prepare contact info for domain service
        $contactInfo = $this->prepareContactData($currentContacts);

        $result = $this->domainService->updateDomainContacts($domain->name, $contactInfo);

        if ($result['success']) {
            $this->syncLocalContacts($domain, $currentContacts);
        }

        return $result;
    }

    /**
     * Handle old form format with contact IDs
     */
    private function handleOldFormFormat(Domain $domain, array $contactIds): array
    {
        // Get current domain contacts to maintain existing ones
        $currentContacts = $this->getCurrentDomainContacts($domain);

        // Merge new contact data with existing contacts
        $allContactIds = array_merge($currentContacts, $contactIds);

        // Convert contact IDs to full contact data for the domain service
        $contactInfo = $this->prepareContactData($allContactIds);

        $result = $this->domainService->updateDomainContacts($domain->name, $contactInfo);

        if ($result['success']) {
            // If contacts were successfully updated at the registrar, update our local records
            $this->syncLocalContacts($domain, $allContactIds);
        }

        return $result;
    }

    /**
     * Check if the data is in new form format
     */
    private function isNewFormFormat(array $data): bool
    {
        return isset($data['contact_type']) && isset($data['first_name']) && isset($data['last_name']);
    }

    /**
     * Create or update contact with form data
     */
    private function createOrUpdateContact(array $data): Contact
    {
        // Format phone number
        $phone = $this->formatPhoneNumber($data['phone_country'] ?? '+250', $data['phone'] ?? '');

        $contactData = [
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'organization' => $data['organization'] ?? null,
            'email' => $data['email'],
            'phone' => $phone,
            'address_one' => $data['address_one'],
            'address_two' => $data['address_two'] ?? null,
            'city' => $data['city'],
            'state_province' => $data['state_province'],
            'postal_code' => $data['postal_code'],
            'country_code' => $data['country_code'],
            'fax_number' => $data['fax_number'] ?? null,
            'user_id' => auth()->id(),
        ];

        // Try to find existing contact by email or create new one
        $contact = Contact::where('email', $data['email'])->first();

        if ($contact) {
            $contact->update($contactData);
        } else {
            $contactData['uuid'] = (string) \Illuminate\Support\Str::uuid();
            $contactData['contact_id'] = 'NC'.mb_strtoupper(\Illuminate\Support\Str::random(8));
            $contactData['contact_type'] = $data['contact_type'];
            $contact = Contact::create($contactData);
        }

        return $contact;
    }

    /**
     * Format phone number for Namecheap API
     */
    private function formatPhoneNumber(string $countryCode, string $number): string
    {
        // Remove any non-digit characters from number
        $number = preg_replace('/[^0-9]/', '', $number);

        // Remove leading + from country code if present
        $countryCode = mb_ltrim($countryCode, '+');

        return "+{$countryCode}.{$number}";
    }

    /**
     * Get current domain contacts from database
     */
    private function getCurrentDomainContacts(Domain $domain): array
    {
        $currentContacts = [];

        // Load current domain contacts
        $domain->load(['contacts' => function ($query) {
            $query->withPivot('type', 'user_id')->withoutGlobalScopes();
        }]);

        foreach ($domain->contacts as $contact) {
            $type = $contact->pivot->type;
            $currentContacts[$type] = ['contact_id' => $contact->id];
        }

        return $currentContacts;
    }

    /**
     * Sync local contact relationships
     */
    private function syncLocalContacts(Domain $domain, array $contactIds): void
    {
        $syncData = [];
        foreach (['registrant', 'admin', 'technical', 'billing'] as $type) {
            if (isset($contactIds[$type]['contact_id'])) {
                $syncData[$contactIds[$type]['contact_id']] = [
                    'type' => $type,
                    'user_id' => auth()->id(),
                ];
            }
        }

        $domain->contacts()->sync($syncData);
    }

    /**
     * Prepare contact data for the domain service
     */
    private function prepareContactData(array $contactIds): array
    {
        $contactInfo = [];

        foreach (['registrant', 'admin', 'technical', 'billing'] as $type) {
            if (isset($contactIds[$type]['contact_id'])) {
                $contact = Contact::findOrFail($contactIds[$type]['contact_id']);

                $contactInfo[$type] = [
                    'first_name' => $contact->first_name,
                    'last_name' => $contact->last_name,
                    'organization' => $contact->organization,
                    'email' => $contact->email,
                    'phone' => $contact->phone,
                    'address_one' => $contact->address_one,
                    'address_two' => $contact->address_two,
                    'city' => $contact->city,
                    'state_province' => $contact->state_province,
                    'postal_code' => $contact->postal_code,
                    'country_code' => $contact->country_code,
                ];
            }
        }

        return $contactInfo;
    }
}
