<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Country;
use App\Services\Domain\EppDomainService;
use App\Services\Domain\InternationalDomainService;

final readonly class CreateDomainContactAction
{
    private CreateDualProviderContactAction $dualProviderContactAction;

    public function __construct(
        EppDomainService $eppService,
        InternationalDomainService $internationalDomainService
    ) {
        $this->dualProviderContactAction = new CreateDualProviderContactAction(
            $eppService,
            $internationalDomainService
        );
    }

    /**
     * Create a contact for a specific domain across both providers.
     *
     * @param  array<string, mixed>  $contactData
     * @return array{epp: object, namecheap: object}
     */
    public function handle(array $contactData, string $domain, string $contactType): array
    {
        $contactData['domain'] = $domain;
        $contactData['contact_type'] = $contactType;
        $contactData = $this->ensureCountryCode($contactData);

        return $this->dualProviderContactAction->handle($contactData, $domain, $contactType);
    }

    /**
     * Ensure contact data contains a country code derived from country_id when present.
     *
     * @param  array<string, mixed>  $contactData
     * @return array<string, mixed>
     */
    private function ensureCountryCode(array $contactData): array
    {
        if (isset($contactData['country_id']) && ! isset($contactData['country_code'])) {
            $country = Country::query()->find($contactData['country_id']);

            if ($country !== null) {
                $contactData['country_code'] = $country->iso_code;
            }
        }

        return $contactData;
    }
}
