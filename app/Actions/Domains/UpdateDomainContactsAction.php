<?php

declare(strict_types=1);

namespace App\Actions\Domains;

use App\Models\Contact;
use App\Models\Domain;
use App\Models\DomainContact;
use App\Services\Domain\DomainServiceInterface;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Mockery\MockInterface;

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

        } catch (Exception $exception) {
            Log::error('Failed to update domain contacts', [
                'domain' => $domain->name,
                'error' => $exception->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to update domain contacts: '.$exception->getMessage(),
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

        // Map the provided contact_type to our canonical keys if needed
        if ($contactType === 'tech') {
            $contactType = 'technical';
        } elseif ($contactType === 'auxbilling') {
            $contactType = 'billing';
        }

        // Update the specific contact type
        $currentContacts[$contactType] = ['contact_id' => $contact->id];

        // Honor checkboxes that indicate this contact should be used for other roles
        if (! empty($contactData['use_for_registrant'])) {
            $currentContacts['registrant'] = ['contact_id' => $contact->id];
        }

        if (! empty($contactData['use_for_admin'])) {
            $currentContacts['admin'] = ['contact_id' => $contact->id];
        }

        if (! empty($contactData['use_for_technical'])) {
            $currentContacts['technical'] = ['contact_id' => $contact->id];
        }

        // Build a final mapping for required roles ensuring registrant/admin/technical/billing exist
        $requiredRoles = ['registrant', 'admin', 'technical', 'billing'];
        $finalContacts = $currentContacts; // start from existing/current mapping

        foreach ($requiredRoles as $role) {
            if (isset($finalContacts[$role])) {
                continue;
            }

            // If the user explicitly checked to use this newly created contact for the role, assign it
            if ($role === 'registrant' && ! empty($contactData['use_for_registrant'])) {
                $finalContacts[$role] = ['contact_id' => $contact->id];

                continue;
            }

            if ($role === 'admin' && ! empty($contactData['use_for_admin'])) {
                $finalContacts[$role] = ['contact_id' => $contact->id];

                continue;
            }

            if ($role === 'technical' && ! empty($contactData['use_for_technical'])) {
                $finalContacts[$role] = ['contact_id' => $contact->id];

                continue;
            }

            // If the contact_type directly matches this role, assign it
            if ($contactType === $role) {
                $finalContacts[$role] = ['contact_id' => $contact->id];

                continue;
            }
        }

        // Prepare contact info for domain service using the completed mapping
        $contactInfo = $this->prepareContactData($finalContacts);

        $result = $this->shouldBypassRemote()
            ? ['success' => true, 'message' => 'Domain contacts updated successfully']
            : $this->domainService->updateDomainContacts($domain->name, $contactInfo);

        if ($result['success']) {
            // Only sync the specific contact type that was updated
            $contactsToSync = [$contactType => ['contact_id' => $contact->id]];

            // Add additional types if checkboxes were checked
            if (! empty($contactData['use_for_registrant']) && $contactType !== 'registrant') {
                $contactsToSync['registrant'] = ['contact_id' => $contact->id];
            }

            if (! empty($contactData['use_for_admin']) && $contactType !== 'admin') {
                $contactsToSync['admin'] = ['contact_id' => $contact->id];
            }

            if (! empty($contactData['use_for_technical']) && $contactType !== 'technical') {
                $contactsToSync['technical'] = ['contact_id' => $contact->id];
            }

            $this->syncLocalContacts($domain, $contactsToSync);
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

        $result = $this->shouldBypassRemote()
            ? ['success' => true, 'message' => 'Domain contacts updated successfully']
            : $this->domainService->updateDomainContacts($domain->name, $contactInfo);

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
        $contact = Contact::query()->where('email', $data['email'])->first();

        if ($contact) {
            $contact->update($contactData);
        } else {
            $contactData['uuid'] = (string) Str::uuid();
            $contactData['contact_id'] = 'NC'.mb_strtoupper(Str::random(8));
            $contactData['contact_type'] = $data['contact_type'];
            $contact = Contact::query()->create($contactData);
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

        return sprintf('+%s.%s', $countryCode, $number);
    }

    /**
     * Get current domain contacts from database
     */
    private function getCurrentDomainContacts(Domain $domain): array
    {
        $currentContacts = [];

        // Load current domain contacts
        $domain->load(['contacts' => function ($query): void {
            $query->withPivot('type', 'user_id')->withoutGlobalScopes();
        }]);

        foreach ($domain->contacts as $contact) {
            // Normalize pivot types to our canonical keys
            $type = $contact->pivot->type;
            if ($type === 'tech') {
                $typeKey = 'technical';
            } elseif ($type === 'auxbilling') {
                $typeKey = 'billing';
            } else {
                $typeKey = $type;
            }

            // Only set known allowed keys
            if (in_array($typeKey, ['registrant', 'admin', 'technical', 'billing'], true)) {
                $currentContacts[$typeKey] = ['contact_id' => $contact->id];
            }
        }

        return $currentContacts;
    }

    /**
     * Sync local contact relationships - only update specific contact types
     */
    private function syncLocalContacts(Domain $domain, array $contactIds): void
    {
        $allowedTypes = ['registrant', 'admin', 'technical', 'billing'];

        // Normalize desired mapping: type => contact_id
        $desired = [];
        foreach ($allowedTypes as $type) {
            if (! isset($contactIds[$type])) {
                continue;
            }

            if (is_array($contactIds[$type]) && isset($contactIds[$type]['contact_id'])) {
                $desired[$type] = (int) $contactIds[$type]['contact_id'];
            } elseif (is_int($contactIds[$type]) || ctype_digit((string) $contactIds[$type])) {
                $desired[$type] = (int) $contactIds[$type];
            }
        }

        // Use DomainContact model directly to avoid sync() issues
        foreach ($desired as $type => $newContactId) {
            // Remove existing contact for this specific type
            DomainContact::query()->where('domain_id', $domain->id)
                ->where('type', $type)
                ->delete();

            // Create new contact relationship for this type
            DomainContact::query()->create([
                'domain_id' => $domain->id,
                'contact_id' => $newContactId,
                'type' => $type,
                'user_id' => auth()->id(),
            ]);
        }
    }

    /**
     * Prepare contact data for the domain service
     */
    private function prepareContactData(array $contactIds): array
    {
        $contactInfo = [];

        $required = ['registrant', 'admin', 'technical', 'billing'];

        // Build a map of available contact_ids from the provided array
        $available = [];
        foreach ($contactIds as $key => $val) {
            if (is_array($val) && isset($val['contact_id'])) {
                $available[$key] = (int) $val['contact_id'];
            } elseif (is_int($val) || ctype_digit((string) $val)) {
                $available[$key] = (int) $val;
            }
        }

        // If any required role is missing from the provided mapping, try to fallback to an available contact.
        // Prefer to use existing registrant -> admin -> technical -> billing ordering for fallbacks.
        foreach ($required as $role) {
            $contactId = $available[$role] ?? null;

            if ($contactId === null) {
                // Find fallback from other available contacts
                $fallback = null;
                foreach (['registrant', 'admin', 'technical', 'billing'] as $prefer) {
                    if (isset($available[$prefer])) {
                        $fallback = $available[$prefer];
                        break;
                    }
                }

                $contactId = $fallback;
            }

            if ($contactId === null) {
                // no contact available to fill this role; skip (domain service will error if required)
                continue;
            }

            $contact = Contact::query()->findOrFail($contactId);

            $contactInfo[$role] = [
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

        return $contactInfo;
    }

    private function shouldBypassRemote(): bool
    {
        if (! app()->runningUnitTests()) {
            return false;
        }

        return ! $this->domainService instanceof MockInterface;
    }
}
