<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Domain;
use App\Services\Domain\NamecheapDomainService;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Log;
use Throwable;

final class DomainOperationsController extends Controller
{
    /**
     * @var NamecheapDomainService
     */
    public NamecheapDomainService $domainService;
    public function __construct(NamecheapDomainService $domainService)
    {
        $this->domainService = $domainService;
    }

    public function domainInfo(Domain $domain): View|Factory
    {
        $domain = Domain::query()->findOrFail($domain->uuid);

        return view('admin.domainOps.info', ['domain' => $domain]);
    }

    public function getContacts(Domain $domain)
    {
        // Debug: write to an independent file to avoid monolog permission problems
        try {
            $debugPath = storage_path('logs/contacts_debug.log');
            $payload = [
                'time' => now()->toDateTimeString(),
                'route_domain' => $domain->name ?? null,
                'route_uuid' => $domain->uuid ?? null,
                'user_id' => auth()->id() ?? null,
            ];
            file_put_contents($debugPath, json_encode($payload) . PHP_EOL, FILE_APPEND | LOCK_EX);
        } catch (\Throwable $e) {
            // ignore filesystem debug failures
        }

        Log::info('getContacts invoked', ['domain' => $domain->name ?? null, 'domain_uuid' => $domain->uuid ?? null, 'user_id' => auth()->id() ?? null]);

        // Call service to retrieve contacts (various services return different shapes)
        $response = $this->domainService->getDomainContacts($domain->name);

        // Also write response to debug file for inspection
        try {
            $debugPath = storage_path('logs/contacts_debug.log');
            file_put_contents($debugPath, json_encode(['time' => now()->toDateTimeString(), 'response' => $response]) . PHP_EOL, FILE_APPEND | LOCK_EX);
        } catch (\Throwable $e) {
            // ignore
        }

        // Normalize different response shapes into a common $contacts array
        $contacts = null;

        // If service returned NamecheapDomainService style: ['success'=>true,'contacts'=>[...]]
        if (is_array($response) && isset($response['contacts']) && is_array($response['contacts'])) {
            $contacts = $response['contacts'];
        }

        // If service returned raw API array (e.g., CommandResponse shape), try to extract it
        if ($contacts === null && isset($response['CommandResponse']['DomainContactsResult'])) {
            $raw = $response['CommandResponse']['DomainContactsResult'];
            // Map possible keys from the raw structure into expected normalized shape
            $contacts = [];
            foreach (['Registrant' => 'registrant', 'Admin' => 'admin', 'Tech' => 'tech', 'AuxBilling' => 'billing'] as $apiKey => $localKey) {
                if (isset($raw[$apiKey]) && is_array($raw[$apiKey])) {
                    $c = $raw[$apiKey];
                    $contacts[$localKey] = [
                        'first_name' => (string) ($c['FirstName'] ?? '' ?? $c['FirstName']),
                        'last_name' => (string) ($c['LastName'] ?? ''),
                        'organization' => (string) ($c['OrganizationName'] ?? ''),
                        'address_one' => (string) ($c['Address1'] ?? ''),
                        'address_two' => (string) ($c['Address2'] ?? ''),
                        'city' => (string) ($c['City'] ?? ''),
                        'state_province' => (string) ($c['StateProvince'] ?? ''),
                        'postal_code' => (string) ($c['PostalCode'] ?? ''),
                        'country_code' => (string) ($c['Country'] ?? ''),
                        'phone' => (string) ($c['Phone'] ?? ''),
                        'email' => (string) ($c['EmailAddress'] ?? ''),
                    ];
                }
            }
        }

        // If still null or empty, log and return with error
        Log::info('Normalized contacts result', ['contacts' => $contacts, 'raw_response' => $response]);

        if (! is_array($contacts) || empty($contacts)) {
            return to_route('admin.domains.index', $domain->uuid)
                ->with('error', 'No contact data available from provider for this domain.');
        }

        foreach ($contacts as $type => $contact) {
            // Ensure contact array shape
            if (! is_array($contact)) {
                Log::warning('Skipping contact - invalid shape', ['type' => $type, 'contact' => $contact]);
                continue;
            }

            // Map provider keys to model fields (fall back to possible alternate keys)
            $email = (string) ($contact['email'] ?? $contact['EmailAddress'] ?? $contact['Email'] ?? '');
            if ($email === '') {
                Log::warning('Skipping contact with empty email (cannot upsert without unique key)', ['type' => $type, 'contact' => $contact]);
                continue;
            }

            $payload = [
                'uuid' => (string) Str::uuid(),
                'first_name' => (string) ($contact['first_name'] ?? $contact['FirstName'] ?? ''),
                'last_name' => (string) ($contact['last_name'] ?? $contact['LastName'] ?? ''),
                'organization' => (string) ($contact['organization'] ?? $contact['OrganizationName'] ?? ''),
                'title' => (string) ($contact['title'] ?? $contact['JobTitle'] ?? ''),
                'address_one' => (string) ($contact['address_one'] ?? $contact['Address1'] ?? ''),
                'address_two' => (string) ($contact['address_two'] ?? $contact['Address2'] ?? ''),
                'city' => (string) ($contact['city'] ?? $contact['City'] ?? ''),
                'state_province' => (string) ($contact['state_province'] ?? $contact['StateProvince'] ?? ''),
                'postal_code' => (string) ($contact['postal_code'] ?? $contact['PostalCode'] ?? ''),
                'country_code' => (string) ($contact['country_code'] ?? $contact['Country'] ?? ''),
                'phone' => (string) ($contact['phone'] ?? $contact['Phone'] ?? ''),
                'email' => $email,
            ];

            Log::info('Attempting to upsert contact', ['type' => $type, 'email' => $email, 'payload' => $payload]);

            try {
                $contactModel = Contact::query()->updateOrCreate(
                    ['email' => $email],
                    $payload
                );

                Log::info('Contact upserted', ['id' => $contactModel->id, 'type' => $type]);

                // Attach to domain via pivot
                $domain->contacts()->syncWithoutDetaching([
                    $contactModel->id => ['type' => $type, 'user_id' => auth()->id() ?? null],
                ]);

                Log::info('Contact attached to domain', ['domain_id' => $domain->id ?? null, 'contact_id' => $contactModel->id, 'type' => $type]);
            } catch (Throwable $e) {
                Log::error('Failed upserting/attaching contact', ['type' => $type, 'error' => $e->getMessage(), 'contact' => $contact]);
            }
        }

        return to_route('admin.domains.index', $domain->uuid)->with('success', 'Contacts imported successfully.');
    }
}
