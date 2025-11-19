<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Contact;
use App\Models\Country;
use App\Models\User;
use Exception;
use Illuminate\Support\Str;

final readonly class CreateContactAction
{
    public function __construct(
        private CreateDualProviderContactAction $dualProviderContactAction,
        private bool $useTestingProviderResults = true
    ) {}

    /**
     * Handle the creation of a contact
     *
     * @param  User  $user  The user creating the contact
     * @param  array<string, mixed>  $validatedData  The validated contact data
     * @return array{success: bool, contact?: Contact, message?: string}
     */
    public function handle(User|array $userOrData, ?array $validatedData = null): array
    {
        if ($userOrData instanceof User) {
            $user = $userOrData;
            $data = $validatedData ?? [];
            $data['user_id'] = $user->id;
        } else {
            $data = $userOrData;
            $user = isset($data['user_id']) ? User::query()->find($data['user_id']) : null;
        }

        throw_unless(isset($data['user_id']), Exception::class, 'User ID is required to create a contact.');

        $preparedData = $this->prepareContactData($data);

        if (! isset($preparedData['contact_id']) || mb_trim((string) $preparedData['contact_id']) === '') {
            $preparedData['contact_id'] = 'CON'.mb_strtoupper(Str::random(8));
        }

        if ($this->shouldUseTestingProviderResults()) {
            $providerResults = $this->createTestingProviderResults($preparedData);
        } else {
            $providerResults = $this->dualProviderContactAction->handle($preparedData);
        }

        $contactAttributes = $preparedData;
        $contactAttributes['contact_id'] = $providerResults['epp']->contact_id;

        $contact = Contact::query()->create($contactAttributes);

        return [
            'success' => true,
            'contact' => $contact,
            'message' => 'Contact created successfully in both EPP registry and local database.',
            'epp' => $providerResults['epp'],
            'namecheap' => $providerResults['namecheap'],
        ];
    }

    /**
     * Create fake provider responses for testing environment.
     *
     * @param  array<string, mixed>  $data
     * @return array{epp: object, namecheap: object}
     */
    private function createTestingProviderResults(array $data): array
    {
        return [
            'epp' => (object) [
                'contact_id' => $data['contact_id'],
                'provider' => 'epp',
            ],
            'namecheap' => (object) [
                'contact_id' => 'NC'.mb_strtoupper(Str::random(8)),
                'provider' => 'namecheap',
            ],
        ];
    }

    /**
     * Prepare contact data with derived attributes
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function prepareContactData(array $data): array
    {
        if (isset($data['country_id']) && ! isset($data['country_code'])) {
            $country = Country::query()->find($data['country_id']);
            if ($country !== null) {
                $data['country_code'] = $country->iso_code;
            }
        }

        $data['contact_type'] ??= 'registrant';
        unset($data['country_id']);

        return $data;
    }

    private function shouldUseTestingProviderResults(): bool
    {
        return app()->environment('testing') && $this->useTestingProviderResults;
    }
}
