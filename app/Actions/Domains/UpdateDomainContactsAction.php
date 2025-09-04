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

    public function handle(Domain $domain, array $contactIds): array
    {
        try {
            // Convert contact IDs to full contact data for the domain service
            $contactInfo = $this->prepareContactData($contactIds);

            $result = $this->domainService->updateDomainContacts($domain->name, $contactInfo);

            if ($result['success']) {
                // If contacts were successfully updated at the registrar, update our local records
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

            return $result;
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
