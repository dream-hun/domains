<?php

declare(strict_types=1);

namespace App\Actions\Domain;

use App\Models\Domain;
use App\Models\DomainContact;
use App\Services\Domain\NamecheapDomainService;
use Illuminate\Support\Facades\Log;
use Throwable;

final readonly class UpdateDomainContactsAction
{
    public function __construct(
        private NamecheapDomainService $domainService
    ) {}

    public function execute(Domain $domain): array
    {
        Log::info('Starting domain contacts update in registry', [
            'domain' => $domain->name,
            'domain_uuid' => $domain->uuid,
            'user_id' => auth()->id(),
        ]);

        try {
            // Get current contacts from database
            $domainContacts = $domain->contacts()->get();

            if ($domainContacts->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'No contacts found for this domain. Please sync contacts from registry first.',
                ];
            }

            // Organize contacts by type
            $contactInfo = [];
            $requiredTypes = ['registrant', 'admin', 'technical', 'billing'];

            // Map our enum values back to API values
            $apiTypeMapping = [
                'registrant' => 'registrant',
                'admin' => 'admin',
                'technical' => 'technical',
                'billing' => 'billing',
            ];

            foreach ($domainContacts as $contact) {
                /** @var Contact $contact */
                /** @var DomainContact $pivotData */
                $pivotData = $contact->pivot;
                $type = $pivotData->type;

                // Use the API type mapping
                $apiType = $apiTypeMapping[$type] ?? $type;

                $contactInfo[$apiType] = [
                    'first_name' => $contact->first_name,
                    'last_name' => $contact->last_name,
                    'organization' => $contact->organization ?? '',
                    'address_one' => $contact->address_one,
                    'address_two' => $contact->address_two ?? '',
                    'city' => $contact->city,
                    'state_province' => $contact->state_province,
                    'postal_code' => $contact->postal_code,
                    'country_code' => $contact->country_code,
                    'phone' => $contact->phone,
                    'email' => $contact->email,
                ];
            }

            $missingTypes = array_diff($requiredTypes, array_keys($contactInfo));
            if ($missingTypes !== []) {
                return [
                    'success' => false,
                    'message' => 'Missing required contact types: '.implode(', ', $missingTypes),
                ];
            }

            // Update contacts in registry
            $response = $this->domainService->updateDomainContacts($domain->name, $contactInfo);

            if (! $response['success']) {
                return [
                    'success' => false,
                    'message' => 'Failed to update contacts in registry: '.($response['message'] ?? 'Unknown error'),
                ];
            }

            Log::info('Domain contacts updated successfully in registry', [
                'domain' => $domain->name,
                'updated_types' => array_keys($contactInfo),
            ]);

            return [
                'success' => true,
                'message' => 'Successfully updated contacts in registry.',
            ];

        } catch (Throwable $throwable) {
            Log::error('Failed to update domain contacts in registry', [
                'domain' => $domain->name,
                'error' => $throwable->getMessage(),
                'trace' => $throwable->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to update contacts: '.$throwable->getMessage(),
            ];
        }
    }
}
