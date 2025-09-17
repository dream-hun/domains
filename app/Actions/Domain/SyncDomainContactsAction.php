<?php

declare(strict_types=1);

namespace App\Actions\Domain;

use App\Models\Contact;
use App\Models\Domain;
use App\Services\Domain\NamecheapDomainService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

final class SyncDomainContactsAction
{
    public function __construct(
        private readonly NamecheapDomainService $domainService
    ) {}

    public function execute(Domain $domain): array
    {
        Log::info('Starting domain contacts sync', [
            'domain' => $domain->name,
            'domain_uuid' => $domain->uuid,
            'user_id' => auth()->id(),
        ]);

        try {
            // Call service to retrieve contacts from registry
            $response = $this->domainService->getDomainContacts($domain->name);

            Log::info('Registry response received', ['response' => $response]);

            // Check if the API call was successful
            if (! $response['success']) {
                return [
                    'success' => false,
                    'message' => 'Failed to retrieve contacts from registry: '.($response['message'] ?? 'Unknown error'),
                ];
            }

            $contacts = $response['contacts'] ?? [];

            if (empty($contacts)) {
                return [
                    'success' => false,
                    'message' => 'No contact data available from provider for this domain.',
                ];
            }

            return $this->syncContacts($domain, $contacts);

        } catch (Throwable $e) {
            Log::error('Failed to sync domain contacts', [
                'domain' => $domain->name,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to sync contacts: '.$e->getMessage(),
            ];
        }
    }

    private function syncContacts(Domain $domain, array $contacts): array
    {
        $processedContacts = [];
        $contactTypes = ['registrant', 'admin', 'tech', 'auxbilling'];

        // Map registry contact types to our enum values
        $contactTypeMapping = [
            'registrant' => 'registrant',
            'admin' => 'admin',
            'tech' => 'technical',
            'auxbilling' => 'billing',
        ];

        // First, detach all existing contacts for this domain
        $domain->contacts()->detach();

        // Process each contact type
        foreach ($contactTypes as $type) {
            if (! isset($contacts[$type])) {
                Log::warning("Contact type '{$type}' not found in registry response", [
                    'available_types' => array_keys($contacts),
                ]);

                continue;
            }

            $contactData = $contacts[$type];

            // Validate required fields
            if (empty($contactData['email'])) {
                Log::warning("Skipping contact type '{$type}' - missing email", [
                    'contact_data' => $contactData,
                ]);

                continue;
            }

            // Map to correct enum value
            $enumContactType = $contactTypeMapping[$type] ?? $type;

            $contactModel = $this->createOrUpdateContact($contactData, $enumContactType);

            if ($contactModel instanceof Contact) {
                // Attach this contact to the domain with the specific type
                $domain->contacts()->attach($contactModel->id, [
                    'type' => $enumContactType,
                    'user_id' => auth()->id(),
                ]);

                $processedContacts[] = [
                    'contact_id' => $contactModel->id,
                    'type' => $enumContactType,
                    'email' => $contactModel->email,
                ];

                Log::info('Contact attached to domain', [
                    'domain_id' => $domain->id,
                    'contact_id' => $contactModel->id,
                    'type' => $enumContactType,
                ]);
            }
        }

        if ($processedContacts === []) {
            return [
                'success' => false,
                'message' => 'No valid contacts could be processed from the registry.',
            ];
        }

        Log::info('Domain contacts synced successfully', [
            'domain_id' => $domain->id,
            'synced_contacts' => count($processedContacts),
            'contact_types' => array_column($processedContacts, 'type'),
        ]);

        return [
            'success' => true,
            'message' => 'Successfully synced '.count($processedContacts).' contact types from registry.',
            'processed_contacts' => $processedContacts,
        ];
    }

    private function createOrUpdateContact(array $contactData, string $enumContactType): ?Contact
    {
        // Prepare contact payload for database
        $contactPayload = [
            'uuid' => (string) Str::uuid(),
            'contact_type' => $enumContactType,
            'first_name' => mb_trim($contactData['first_name'] ?? ''),
            'last_name' => mb_trim($contactData['last_name'] ?? ''),
            'organization' => mb_trim($contactData['organization'] ?? ''),
            'title' => null, // Not provided by Namecheap API
            'address_one' => mb_trim($contactData['address_one'] ?? ''),
            'address_two' => in_array(mb_trim($contactData['address_two'] ?? ''), ['', '0'], true) ? null : mb_trim($contactData['address_two'] ?? ''),
            'city' => mb_trim($contactData['city'] ?? ''),
            'state_province' => mb_trim($contactData['state_province'] ?? ''),
            'postal_code' => mb_trim($contactData['postal_code'] ?? ''),
            'country_code' => mb_strtoupper(mb_trim($contactData['country_code'] ?? '')),
            'phone' => mb_trim($contactData['phone'] ?? ''),
            'phone_extension' => null, // Not provided by Namecheap API
            'fax_number' => null, // Not provided by Namecheap API
            'email' => mb_trim($contactData['email']),
            'user_id' => auth()->id(),
        ];

        Log::info("Processing contact type '{$enumContactType}'", [
            'email' => $contactPayload['email'],
        ]);

        try {
            // Create or update contact based on email only
            $contactModel = Contact::updateOrCreate(
                ['email' => $contactPayload['email']],
                $contactPayload
            );

            Log::info('Contact upserted successfully', [
                'contact_id' => $contactModel->id,
                'type' => $enumContactType,
                'email' => $contactModel->email,
            ]);

            return $contactModel;

        } catch (Throwable $e) {
            Log::error("Failed to upsert contact for type '{$enumContactType}'", [
                'error' => $e->getMessage(),
                'email' => $contactPayload['email'],
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }
}
