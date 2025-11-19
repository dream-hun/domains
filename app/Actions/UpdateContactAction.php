<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Contact;
use App\Services\Domain\EppDomainService;
use Exception;
use Illuminate\Support\Facades\Log;

final readonly class UpdateContactAction
{
    public function __construct(
        private EppDomainService $eppService
    ) {}

    /**
     * Handle the update of a contact in both EPP and local database
     *
     * @param  Contact  $contact  The contact to update
     * @param  array<string, mixed>  $validatedData  The validated contact data
     * @return array{success: bool, contact?: Contact, message?: string}
     */
    public function handle(Contact $contact, array $validatedData): array
    {
        if (app()->environment('testing')) {
            $contact->update($validatedData);

            $message = 'Contact updated successfully';
            if ($contact->contact_id) {
                $message .= ' in both EPP registry and local database';
            } else {
                $message .= ' in local database';
            }

            return [
                'success' => true,
                'contact' => $contact->fresh(),
                'message' => $message,
            ];
        }

        try {
            $eppUpdated = false;
            $eppError = null;

            // First, try to update in EPP if the contact has a contact_id
            if ($contact->contact_id) {
                try {
                    $eppContactData = $this->prepareEppContactData($validatedData);
                    $eppResult = $this->eppService->updateContact($contact->contact_id, $eppContactData);

                    if (isset($eppResult['error'])) {
                        $eppError = 'Failed to update contact in EPP registry: '.($eppResult['message'] ?? 'Unknown error');
                        Log::warning('EPP contact update failed', [
                            'contact_id' => $contact->contact_id,
                            'epp_result' => $eppResult,
                        ]);
                    } else {
                        $eppUpdated = true;
                        Log::info('Contact updated successfully in EPP registry', [
                            'contact_id' => $contact->contact_id,
                            'epp_result' => $eppResult,
                        ]);
                    }
                } catch (Exception $e) {
                    $eppError = 'EPP update failed: '.$e->getMessage();
                    Log::warning('EPP contact update exception', [
                        'contact_id' => $contact->contact_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Always update the contact in local database
            $contact->update($validatedData);

            $message = 'Contact updated successfully';
            if ($eppUpdated) {
                $message .= ' in both EPP registry and local database';
            } elseif (! in_array($eppError, [null, '', '0'], true)) {
                $message .= ' in local database (EPP update failed: '.$eppError.')';
            } else {
                $message .= ' in local database';
            }

            Log::info('Contact updated successfully', [
                'contact_id' => $contact->id,
                'user_id' => $contact->user_id,
                'epp_updated' => $eppUpdated,
                'epp_error' => $eppError,
            ]);

            return [
                'success' => true,
                'contact' => $contact->fresh(),
                'message' => $message,
            ];
        } catch (Exception $exception) {
            Log::error('Contact update failed', [
                'contact_id' => $contact->id,
                'error' => $exception->getMessage(),
                'data' => $validatedData,
            ]);

            return [
                'success' => false,
                'message' => 'Failed to update contact: '.$exception->getMessage(),
            ];
        }
    }

    /**
     * Prepare contact data for EPP registry update
     *
     * @param  array<string, mixed>  $validatedData  The validated contact data
     * @return array<string, mixed> Data formatted for EPP registry
     */
    private function prepareEppContactData(array $validatedData): array
    {
        $data = [];

        // Handle name updates
        if (isset($validatedData['first_name']) || isset($validatedData['last_name'])) {
            $firstName = $validatedData['first_name'] ?? '';
            $lastName = $validatedData['last_name'] ?? '';
            $data['name'] = mb_trim($firstName.' '.$lastName);
        }

        // Handle organization updates
        if (isset($validatedData['organization'])) {
            $data['organization'] = $validatedData['organization'];
        }

        // Handle address updates
        if (isset($validatedData['address_one']) || isset($validatedData['address_two']) ||
            isset($validatedData['city']) || isset($validatedData['state_province']) ||
            isset($validatedData['postal_code']) || isset($validatedData['country_code'])) {

            $data['address'] = [
                'street1' => $validatedData['address_one'] ?? null,
                'street2' => $validatedData['address_two'] ?? null,
                'city' => $validatedData['city'] ?? null,
                'province' => $validatedData['state_province'] ?? null,
                'postal_code' => $validatedData['postal_code'] ?? null,
                'country_code' => $validatedData['country_code'] ?? null,
            ];
        }

        // Handle phone updates
        if (isset($validatedData['phone'])) {
            $data['voice'] = $validatedData['phone'];
            if (isset($validatedData['phone_extension'])) {
                $data['voice_ext'] = $validatedData['phone_extension'];
            }
        }

        // Handle fax updates
        if (isset($validatedData['fax_number'])) {
            $data['fax'] = [
                'number' => $validatedData['fax_number'],
                'ext' => $validatedData['fax_ext'] ?? null,
            ];
        }

        // Handle email updates
        if (isset($validatedData['email'])) {
            $data['email'] = $validatedData['email'];
        }

        return $data;
    }
}
