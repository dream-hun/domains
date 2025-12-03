<?php

declare(strict_types=1);

namespace App\Actions;

use App\Services\Domain\EppDomainService;
use App\Services\Domain\InternationalDomainService;
use Exception;
use Illuminate\Support\Str;
use stdClass;

final readonly class CreateDualProviderContactAction
{
    public function __construct(
        private EppDomainService $eppService,
        private InternationalDomainService $internationalDomainService
    ) {}

    /**
     * Create contacts across both the local EPP registry and the international provider.
     *
     * @param  array<string, mixed>  $contactData
     * @return array{epp: object, namecheap: object}
     */
    public function handle(array $contactData, ?string $domain = null, ?string $contactType = null): array
    {
        if (! isset($contactData['contact_id']) || mb_trim((string) $contactData['contact_id']) === '') {
            $contactData['contact_id'] = 'CON'.mb_strtoupper(Str::random(8));
        }

        $eppPayload = $this->prepareEppContactData($contactData);

        try {
            $eppResult = $this->eppService->createContacts($eppPayload);
        } catch (Exception $exception) {
            throw new Exception('Failed to create contact in EPP: '.$exception->getMessage(), 0, $exception);
        }

        if (! isset($eppResult['contact_id']) || $eppResult['contact_id'] === '') {
            $message = $eppResult['message'] ?? 'Unknown error';

            throw new Exception('Failed to create contact in EPP: '.$message);
        }

        $internationalContact = $this->internationalDomainService->createContact($this->prepareInternationalContactData(
            $contactData,
            $domain,
            $contactType
        ));

        $internationalContactId = (string) $internationalContact->contact_id;

        return [
            'epp' => $this->formatProviderResult(
                (string) $eppResult['contact_id'],
                'epp',
                $domain,
                $contactType
            ),
            'namecheap' => $this->formatProviderResult(
                $internationalContactId,
                'namecheap',
                $domain,
                $contactType
            ),
        ];
    }

    /**
     * Prepare payload for EPP contact creation.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function prepareEppContactData(array $data): array
    {
        return [
            'contact_id' => $data['contact_id'],
            'name' => mb_trim(($data['first_name'] ?? '').' '.($data['last_name'] ?? '')),
            'organization' => $data['organization'] ?? null,
            'street1' => $data['address_one'] ?? '',
            'street2' => $data['address_two'] ?? null,
            'city' => $data['city'] ?? '',
            'province' => $data['state_province'] ?? '',
            'postal_code' => $data['postal_code'] ?? '',
            'country_code' => $data['country_code'] ?? '',
            'voice' => $data['phone'] ?? '',
            'voice_ext' => $data['phone_extension'] ?? null,
            'fax' => $data['fax_number'] ?? null,
            'email' => $data['email'] ?? '',
            'postal_type' => 'int',
        ];
    }

    /**
     * Prepare data for the international provider contact request.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function prepareInternationalContactData(array $data, ?string $domain, ?string $contactType): array
    {
        return [
            'domain' => $domain ?? $data['domain'] ?? null,
            'first_name' => $data['first_name'] ?? '',
            'last_name' => $data['last_name'] ?? '',
            'organization' => $data['organization'] ?? null,
            'email' => $data['email'] ?? '',
            'phone' => $data['phone'] ?? '',
            'address_one' => $data['address_one'] ?? '',
            'address_two' => $data['address_two'] ?? null,
            'city' => $data['city'] ?? '',
            'state_province' => $data['state_province'] ?? '',
            'postal_code' => $data['postal_code'] ?? '',
            'country_code' => $data['country_code'] ?? '',
            'user_id' => $data['user_id'] ?? null,
            'contact_type' => $contactType ?? $data['contact_type'] ?? 'registrant',
        ];
    }

    private function formatProviderResult(string $contactId, string $provider, ?string $domain, ?string $contactType): stdClass
    {
        return (object) [
            'contact_id' => $contactId,
            'provider' => $provider,
            'domain' => $domain,
            'contact_type' => $contactType,
        ];
    }
}
