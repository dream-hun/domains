<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Models\Contact;
use Exception;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use SimpleXMLElement;

class InternationalDomainService
{
    private readonly string $apiUser;

    private readonly string $apiKey;

    private readonly string $username;

    private readonly string $clientIp;

    private readonly string $apiBaseUrl;

    public function __construct()
    {
        $this->apiUser = (string) config('services.namecheap.apiUser', '');
        $this->apiKey = (string) config('services.namecheap.apiKey', '');
        $this->username = (string) config('services.namecheap.username', '');
        $this->clientIp = (string) config('services.namecheap.client', '');
        $this->apiBaseUrl = (string) config('services.namecheap.apiBaseUrl', '');
    }

    /**
     * Check domain availability through Namecheap API.
     *
     * @param  array<int, string>  $domains
     * @return array<string, mixed>
     */
    public function checkAvailability(array $domains): array
    {
        $response = $this->callApi('namecheap.domains.check', [
            'DomainList' => implode(',', $domains),
        ]);

        $results = [];

        foreach ($response->CommandResponse->DomainCheckResult as $result) {
            $domain = (string) $result['Domain'];
            $results[$domain] = [
                'available' => ((string) $result['Available']) === 'true',
                'reason' => (string) ($result['Description'] ?? ''),
            ];
        }

        if (count($domains) === 1) {
            return $results[$domains[0]] ?? [
                'available' => false,
                'reason' => 'Domain not found in response.',
            ];
        }

        return $results;
    }

    /**
     * Retrieve domain information from Namecheap.
     *
     * @return array<string, mixed>
     */
    public function getDomainInfo(string $domain): array
    {
        $response = $this->callApi('namecheap.domains.getInfo', [
            'DomainName' => $domain,
        ]);

        $info = $response->CommandResponse->DomainGetInfoResult;

        $status = isset($info['Status']) ? [(string) $info['Status']] : [];

        return [
            'success' => true,
            'domain' => (string) ($info['Name'] ?? $domain),
            'status' => $status,
            'registrant' => (string) ($info['Registrant'] ?? ''),
            'created_date' => (string) ($info['CreatedDate'] ?? ''),
            'expiry_date' => (string) ($info['ExpiredDate'] ?? ''),
        ];
    }

    /**
     * Suggest domains using Namecheap suggestions.
     *
     * @return array<string, array{available: bool}>
     */
    public function suggestDomains(string $keyword): array
    {
        $response = $this->callApi('namecheap.domains.check', [
            'DomainList' => $keyword,
        ]);

        $suggestions = [];

        foreach ($response->CommandResponse->DomainCheckResult as $result) {
            $domain = (string) $result['Domain'];
            $suggestions[$domain] = [
                'available' => ((string) $result['Available']) === 'true',
            ];
        }

        return $suggestions;
    }

    /**
     * Create a Namecheap contact and return a Contact model (not persisted).
     *
     * @param  array<string, mixed>  $contactData
     */
    public function createContact(array $contactData): Contact
    {
        $payload = $this->prepareContactPayload($contactData);

        $response = $this->callApi('namecheap.domains.contacts.create', $payload);

        $contactId = (string) ($response->CommandResponse->ContactCreateResult['ContactID'] ?? '');

        throw_if($contactId === '', Exception::class, 'Failed to create Namecheap contact: Missing contact ID in response.');

        $contact = new Contact();
        $contact->setAttribute('contact_id', $contactId);
        $contact->setAttribute('first_name', $payload['FirstName'] ?? '');
        $contact->setAttribute('last_name', $payload['LastName'] ?? '');
        $contact->setAttribute('email', $payload['EmailAddress'] ?? '');
        $contact->setAttribute('phone', $payload['Phone'] ?? '');
        $contact->setAttribute('provider', 'namecheap');

        return $contact;
    }

    /**
     * Register a domain using Namecheap.
     *
     * @param  array<string, mixed>  $contactInfo
     * @return array<string, mixed>
     */
    public function registerDomain(string $domain, array $contactInfo, int $years = 1): array
    {
        $this->createContact(array_merge($contactInfo, [
            'domain' => $domain,
        ]));

        $response = $this->callApi('namecheap.domains.create', [
            'DomainName' => $domain,
            'Years' => $years,
            'RegistrantFirstName' => $contactInfo['first_name'] ?? '',
            'RegistrantLastName' => $contactInfo['last_name'] ?? '',
            'RegistrantEmailAddress' => $contactInfo['email'] ?? '',
            'RegistrantPhone' => $this->formatPhone($contactInfo['phone'] ?? ''),
            'RegistrantAddress1' => $contactInfo['address_one'] ?? '',
            'RegistrantCity' => $contactInfo['city'] ?? '',
            'RegistrantStateProvince' => $contactInfo['state_province'] ?? '',
            'RegistrantPostalCode' => $contactInfo['postal_code'] ?? '',
            'RegistrantCountry' => $contactInfo['country_code'] ?? '',
            'RegistrantOrganizationName' => $contactInfo['organization'] ?? '',
            'RegistrantContactType' => $contactInfo['contact_type'] ?? 'registrant',
        ]);

        $orderId = (string) ($response->CommandResponse->DomainCreateResult['OrderId'] ?? '');

        return [
            'success' => true,
            'domain' => $domain,
            'message' => $orderId !== ''
                ? sprintf('Domain registered successfully. Order ID: %s', $orderId)
                : 'Domain registered successfully.',
        ];
    }

    /**
     * Extract the first name from a full name string.
     */
    private function extractFirstName(string $fullName): string
    {
        $parts = preg_split('/\s+/', mb_trim($fullName));

        return $parts[0] ?? '';
    }

    /**
     * Extract the last name from a full name string.
     */
    private function extractLastName(string $fullName): string
    {
        $parts = preg_split('/\s+/', mb_trim($fullName));

        array_shift($parts);

        return implode(' ', $parts);
    }

    /**
     * Build the query parameters for Namecheap API calls.
     *
     * @param  array<string, string|int|null>  $extra
     * @return array<string, string>
     */
    private function buildParams(array $extra = []): array
    {
        return array_filter(array_merge([
            'ApiUser' => $this->apiUser,
            'ApiKey' => $this->apiKey,
            'UserName' => $this->username,
            'ClientIp' => $this->clientIp,
        ], $extra), static fn ($value): bool => $value !== null && $value !== '');
    }

    /**
     * Execute a Namecheap API call and return the parsed XML response.
     *
     * @param  array<string, string|int|null>  $params
     */
    private function callApi(string $command, array $params): SimpleXMLElement
    {
        $query = $this->buildParams(array_merge($params, [
            'Command' => $command,
        ]));

        /** @var Response $response */
        $response = Http::get($this->apiBaseUrl, $query);

        if (! $response->successful()) {
            throw new Exception('Namecheap API request failed with status '.$response->status());
        }

        return $this->parseXmlResponse($response->body());
    }

    private function parseXmlResponse(string $body): SimpleXMLElement
    {
        $normalized = preg_replace('/xmlns="[^"]+"/', '', $body);

        $xml = simplexml_load_string($normalized ?? $body);

        throw_if($xml === false, Exception::class, 'Unable to parse Namecheap API response.');

        if (isset($xml['Status']) && (string) $xml['Status'] === 'ERROR') {
            $error = (string) ($xml->Errors->Error[0] ?? 'Unknown error');

            throw new Exception('Namecheap API error: '.$error);
        }

        return $xml;
    }

    /**
     * Prepare payload for Namecheap contact creation.
     *
     * @param  array<string, mixed>  $contactData
     * @return array<string, string>
     */
    private function prepareContactPayload(array $contactData): array
    {
        $fullName = mb_trim(($contactData['first_name'] ?? '').' '.($contactData['last_name'] ?? ''));

        if ($fullName === '') {
            $fullName = $contactData['name'] ?? '';
        }

        return array_filter([
            'DomainName' => $contactData['domain'] ?? null,
            'FirstName' => $contactData['first_name'] ?? $this->extractFirstName($fullName),
            'LastName' => $contactData['last_name'] ?? $this->extractLastName($fullName),
            'EmailAddress' => $contactData['email'] ?? '',
            'Phone' => $this->formatPhone($contactData['phone'] ?? ''),
            'Address1' => $contactData['address_one'] ?? '',
            'Address2' => $contactData['address_two'] ?? null,
            'City' => $contactData['city'] ?? '',
            'StateProvince' => $contactData['state_province'] ?? '',
            'PostalCode' => $contactData['postal_code'] ?? '',
            'Country' => mb_strtoupper((string) ($contactData['country_code'] ?? '')),
            'OrganizationName' => $contactData['organization'] ?? '',
        ], static fn ($value): bool => $value !== null);
    }

    private function formatPhone(string $phone): string
    {
        $sanitized = preg_replace('/[^\d\+]/', '', $phone);

        if ($sanitized === null || $sanitized === '') {
            return '+1.0000000000';
        }

        if (! Str::startsWith($sanitized, '+')) {
            $sanitized = '+'.$sanitized;
        }

        if (! str_contains($sanitized, '.')) {
            return Str::replaceFirst('+', '+1.', $sanitized);
        }

        return $sanitized;
    }
}
