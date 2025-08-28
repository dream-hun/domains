<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Contact;
use App\Models\User;
use App\Services\Domain\EppDomainService;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final readonly class CreateContactAction
{
    public function __construct(
        private EppDomainService $eppService
    ) {}

    /**
     * Handle the creation of a contact
     *
     * @param  User  $user  The user creating the contact
     * @param  array<string, mixed>  $validatedData  The validated contact data
     * @return array{success: bool, contact?: Contact, message?: string}
     */
    public function handle(User $user, array $validatedData): array
    {
        try {

            $validatedData['user_id'] = $user->id;
            if (! isset($validatedData['contact_id'])) {
                $validatedData['contact_id'] = 'CON'.mb_strtoupper(Str::random(8));
            }
            $eppContactData = $this->prepareEppContactData($validatedData);
            $eppResult = $this->eppService->createContacts($eppContactData);

            if (! isset($eppResult['contact_id']) || isset($eppResult['error'])) {
                throw new Exception('Failed to create contact in EPP registry: '.($eppResult['message'] ?? 'Unknown error'));
            }
            $validatedData['contact_id'] = $eppResult['contact_id'];
            $contact = Contact::create(['uuid' => (string) Str::uuid()] + $validatedData);

            Log::info('Contact created successfully in both EPP registry and local database', [
                'contact_id' => $contact->contact_id,
                'epp_contact_id' => $eppResult['contact_id'],
                'user_id' => $user->id,
            ]);

            return [
                'success' => true,
                'contact' => $contact,
                'message' => 'Contact created successfully in both EPP registry and local database.',
            ];
        } catch (Exception $e) {
            Log::error('Contact creation failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'data' => $validatedData,
            ]);

            return [
                'success' => false,
                'message' => 'Failed to create contact: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Prepare contact data for EPP registry creation
     *
     * @param  array<string, mixed>  $validatedData  The validated contact data
     * @return array<string, mixed> Data formatted for EPP registry
     */
    private function prepareEppContactData(array $validatedData): array
    {
        return [
            'contact_id' => $validatedData['contact_id'],
            'name' => $validatedData['first_name'].' '.$validatedData['last_name'],
            'organization' => $validatedData['organization'] ?? null,
            'street1' => $validatedData['address_one'],
            'street2' => $validatedData['address_two'] ?? null,
            'city' => $validatedData['city'],
            'province' => $validatedData['state_province'],
            'postal_code' => $validatedData['postal_code'],
            'country_code' => $validatedData['country_code'],
            'voice' => $validatedData['phone'],
            'voice_ext' => $validatedData['phone_extension'] ?? null,
            'fax' => $validatedData['fax_number'] ?? null,
            'email' => $validatedData['email'],
            'postal_type' => 'int', // international format
        ];
    }
}
