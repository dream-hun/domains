<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Enums\DomainType;
use App\Models\Contact;
use App\Models\DomainPrice;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use SimpleXMLElement;

final class NamecheapDomainService implements DomainRegistrationServiceInterface, DomainServiceInterface
{
    private string $apiUser;

    private string $apiKey;

    private string $username;

    private string $clientIp;

    private string $apiBaseUrl;

    public function __construct()
    {
        $this->apiUser = config('services.namecheap.apiUser');
        $this->apiKey = config('services.namecheap.apiKey');
        $this->username = config('services.namecheap.username');
        $this->clientIp = config('services.namecheap.client');
        $this->apiBaseUrl = config('services.namecheap.apiBaseUrl');

        // Validate required configuration
        if ($this->apiUser === '' || $this->apiUser === '0' || ($this->apiKey === '' || $this->apiKey === '0') || ($this->username === '' || $this->username === '0') || ($this->clientIp === '' || $this->clientIp === '0') || ($this->apiBaseUrl === '' || $this->apiBaseUrl === '0')) {
            Log::warning('Namecheap API configuration incomplete', [
                'has_api_user' => $this->apiUser !== '' && $this->apiUser !== '0',
                'has_api_key' => $this->apiKey !== '' && $this->apiKey !== '0',
                'has_username' => $this->username !== '' && $this->username !== '0',
                'has_client_ip' => $this->clientIp !== '' && $this->clientIp !== '0',
                'has_api_base_url' => $this->apiBaseUrl !== '' && $this->apiBaseUrl !== '0',
            ]);
        }
    }

    /**
     * Check domain availability
     */
    public function checkAvailability(array $domain): array
    {
        try {
            $query = http_build_query([
                'ApiUser' => $this->apiUser,
                'ApiKey' => $this->apiKey,
                'UserName' => $this->username,
                'ClientIp' => $this->clientIp,
                'Command' => 'namecheap.domains.check',
                'DomainList' => implode(',', $domain),
            ]);

            $url = $this->apiBaseUrl.'?'.$query;
            $response = Http::timeout(60)->get($url);

            Log::info('Namecheap domain check response', [
                'url' => $this->apiBaseUrl,
                'body' => $response->body(),
                'domains' => $domain,
            ]);

            if (! $response->successful()) {
                throw new Exception('API request failed with status: '.$response->status());
            }

            if (empty($response->body())) {
                throw new Exception('Namecheap API error: Empty response from API.');
            }

            try {
                $xml = new SimpleXMLElement($response->body());
            } catch (Exception $e) {
                Log::error('Failed to parse Namecheap API XML response', [
                    'body' => $response->body(),
                    'error' => $e->getMessage(),
                ]);
                throw new Exception('Namecheap API error: Malformed XML response.', $e->getCode(), $e);
            }

            // Check for API-level errors
            if (property_exists($xml, 'Errors') && $xml->Errors !== null && (property_exists($xml->Errors, 'Error') && $xml->Errors->Error !== null)) {
                $errorMsg = (string) $xml->Errors->Error;
                if ($errorMsg !== '' && $errorMsg !== '0') {
                    throw new Exception('Namecheap API error: '.$errorMsg);
                }
            }

            // Check for response status
            if (isset($xml['Status']) && mb_strtolower((string) $xml['Status']) === 'error') {
                $errorMsg = 'API returned error status';
                if (property_exists($xml, 'Errors') && $xml->Errors !== null) {
                    $errorMsg = (string) $xml->Errors->Error ?? $errorMsg;
                }
                throw new Exception('Namecheap API error: '.$errorMsg);
            }

            $results = [];
            if (! property_exists($xml->CommandResponse, 'DomainCheckResult') || $xml->CommandResponse->DomainCheckResult === null) {
                throw new Exception('Namecheap API error: Missing DomainCheckResult in response.');
            }
            $domainCheckResults = $xml->CommandResponse->DomainCheckResult;

            foreach ($domainCheckResults as $result) {
                $domainName = (string) $result['Domain'];
                $available = mb_strtolower((string) $result['Available']) === 'true';

                // Handle various error scenarios
                $errorMessage = null;
                if (! $available) {
                    $errorMessage = (string) $result['ErrorMessage'];
                    if ($errorMessage === '' || $errorMessage === '0') {
                        $errorMessage = 'Domain not available.';
                    }
                }

                Log::debug('Namecheap domain check result', [
                    'domain' => $domainName,
                    'available' => $available,
                    'raw_available' => (string) $result['Available'],
                    'error_message' => $errorMessage,
                    'full_result' => (array) $result,
                ]);

                $results[$domainName] = (object) [
                    'available' => $available,
                    'error' => $errorMessage,
                ];
            }

            return $results;
        } catch (Exception $e) {
            Log::error('Namecheap domain check error', [
                'domains' => $domain,
                'message' => $e->getMessage(),
            ]);
            $results = [];
            foreach ($domain as $d) {
                $results[$d] = (object) [
                    'available' => false,
                    'error' => $e->getMessage(),
                ];
            }

            return $results;
        }
    }

    /**
     * Register a domain using Namecheap API
     */
    public function registerDomain(string $domain, array $contactInfo, int $years): array
    {
        try {
            // Validate domain name
            if (! $this->isValidDomainName($domain)) {
                throw new Exception('Invalid domain name format');
            }

            // Validate registration years
            if ($years < 1 || $years > 10) {
                throw new Exception('Registration years must be between 1 and 10');
            }

            // Validate required contact information
            $this->validateContactInfo($contactInfo);

            // Build registration request
            $params = [
                'ApiUser' => $this->apiUser,
                'ApiKey' => $this->apiKey,
                'UserName' => $this->username,
                'ClientIp' => $this->clientIp,
                'Command' => 'namecheap.domains.create',
                'DomainName' => $domain,
                'Years' => $years,
                'AddFreeWhoisguard' => 'no',
                'WGEnabled' => 'no',
                'GenerateAdminOrderRefId' => 'false',
            ];

            // Add contact details for each role
            $params = $this->addContactDetails($contactInfo, $params);

            // Add TLD-specific parameters
            $tld = $this->extractTld($domain);
            $params = array_merge($params, $this->getTldSpecificParams($tld));

            // Add premium domain pricing if applicable
            $pricing = $this->getDomainPricing($domain);
            if ($pricing['success'] && isset($pricing['is_premium']) && $pricing['is_premium']) {
                $params['IsPremiumDomain'] = 'true';
                $params['PremiumPrice'] = $pricing['price'];
                if (isset($pricing['eap_fee']) && $pricing['eap_fee'] > 0) {
                    $params['EapFee'] = $pricing['eap_fee'];
                }
            }

            Log::info('Namecheap domain registration request', [
                'domain' => $domain,
                'years' => $years,
                'params' => array_merge($params, ['ApiKey' => '[HIDDEN]']), // Hide API key in logs
            ]);

            // Make API call
            $xml = $this->makeApiCall($params);

            // Validate successful registration
            if (! property_exists($xml->CommandResponse, 'DomainCreateResult') || $xml->CommandResponse->DomainCreateResult === null) {
                throw new Exception('Invalid API response: Missing DomainCreateResult');
            }

            $result = $xml->CommandResponse->DomainCreateResult;
            if ((string) $result['Registered'] !== 'true') {
                $description = (string) ($result['Description'] ?? 'Unknown error');
                throw new Exception('Domain registration failed: '.$description);
            }

            return [
                'success' => true,
                'domain' => $domain,
                'message' => 'Domain registered successfully',
                'registered' => true,
                'orderid' => (string) ($result['OrderID'] ?? ''),
            ];

        } catch (Exception $e) {
            Log::error('Namecheap domain registration failed', [
                'domain' => $domain,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Enable/disable domain lock
     */
    public function setDomainLock(string $domain, bool $lock): array
    {
        try {
            $params = [
                'ApiUser' => $this->apiUser,
                'ApiKey' => $this->apiKey,
                'UserName' => $this->username,
                'ClientIp' => $this->clientIp,
                'Command' => 'namecheap.domains.setRegistrarLock',
                'DomainName' => $domain,
                'LockAction' => $lock ? 'LOCK' : 'UNLOCK',
            ];

            $xml = $this->makeApiCall($params);

            if (! property_exists($xml->CommandResponse, 'DomainSetRegistrarLockResult') || $xml->CommandResponse->DomainSetRegistrarLockResult === null) {
                throw new Exception('Invalid API response: Missing DomainSetRegistrarLockResult');
            }

            $result = $xml->CommandResponse->DomainSetRegistrarLockResult;
            $isSuccess = mb_strtolower((string) $result['IsSuccess']) === 'true';

            if (! $isSuccess) {
                throw new Exception('Failed to update domain lock status');
            }

            return [
                'success' => true,
                'domain' => $domain,
                'locked' => $lock,
                'message' => $lock ? 'Domain locked successfully' : 'Domain unlocked successfully',
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to update domain lock: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Get domain lock status
     */
    public function getDomainLock(string $domain): array
    {
        try {
            $params = [
                'ApiUser' => $this->apiUser,
                'ApiKey' => $this->apiKey,
                'UserName' => $this->username,
                'ClientIp' => $this->clientIp,
                'Command' => 'namecheap.domains.getRegistrarLock',
                'DomainName' => $domain,
            ];

            $xml = $this->makeApiCall($params);

            if (! property_exists($xml->CommandResponse, 'DomainGetRegistrarLockResult') || $xml->CommandResponse->DomainGetRegistrarLockResult === null) {
                throw new Exception('Invalid API response: Missing DomainGetRegistrarLockResult');
            }

            $result = $xml->CommandResponse->DomainGetRegistrarLockResult;
            $locked = mb_strtolower((string) $result['RegistrarLockStatus']) === 'true';

            return [
                'success' => true,
                'domain' => $domain,
                'locked' => $locked,
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to get domain lock status: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Enable WhoisGuard privacy protection
     */
    public function enableWhoisGuard(string $domain): array
    {
        try {
            $params = [
                'ApiUser' => $this->apiUser,
                'ApiKey' => $this->apiKey,
                'UserName' => $this->username,
                'ClientIp' => $this->clientIp,
                'Command' => 'namecheap.whoisguard.enable',
                'WhoisguardID' => $this->getWhoisGuardId($domain),
                'ForwardedToEmail' => 'admin@'.$domain,
            ];

            $xml = $this->makeApiCall($params);

            if (! property_exists($xml->CommandResponse, 'WhoisguardEnableResult') || $xml->CommandResponse->WhoisguardEnableResult === null) {
                throw new Exception('Invalid API response: Missing WhoisguardEnableResult');
            }

            $result = $xml->CommandResponse->WhoisguardEnableResult;
            $isSuccess = mb_strtolower((string) $result['IsSuccess']) === 'true';

            if (! $isSuccess) {
                throw new Exception('Failed to enable WhoisGuard');
            }

            return [
                'success' => true,
                'domain' => $domain,
                'message' => 'WhoisGuard enabled successfully',
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to enable WhoisGuard: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Disable WhoisGuard privacy protection
     */
    public function disableWhoisGuard(string $domain): array
    {
        try {
            $params = [
                'ApiUser' => $this->apiUser,
                'ApiKey' => $this->apiKey,
                'UserName' => $this->username,
                'ClientIp' => $this->clientIp,
                'Command' => 'namecheap.whoisguard.disable',
                'WhoisguardID' => $this->getWhoisGuardId($domain),
            ];

            $xml = $this->makeApiCall($params);

            if (! property_exists($xml->CommandResponse, 'WhoisguardDisableResult') || $xml->CommandResponse->WhoisguardDisableResult === null) {
                throw new Exception('Invalid API response: Missing WhoisguardDisableResult');
            }

            $result = $xml->CommandResponse->WhoisguardDisableResult;
            $isSuccess = mb_strtolower((string) $result['IsSuccess']) === 'true';

            if (! $isSuccess) {
                throw new Exception('Failed to disable WhoisGuard');
            }

            return [
                'success' => true,
                'domain' => $domain,
                'message' => 'WhoisGuard disabled successfully',
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to disable WhoisGuard: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Get domain contacts
     */
    public function getDomainContacts(string $domain): array
    {
        try {
            $params = [
                'ApiUser' => $this->apiUser,
                'ApiKey' => $this->apiKey,
                'UserName' => $this->username,
                'ClientIp' => $this->clientIp,
                'Command' => 'namecheap.domains.getContacts',
                'DomainName' => $domain,
            ];

            $xml = $this->makeApiCall($params);

            if (! property_exists($xml->CommandResponse, 'DomainContactsResult') || $xml->CommandResponse->DomainContactsResult === null) {
                throw new Exception('Invalid API response: Missing DomainContactsResult');
            }

            $result = $xml->CommandResponse->DomainContactsResult;
            $contacts = [];

            $contactTypes = ['Registrant', 'Admin', 'Tech', 'AuxBilling'];

            foreach ($contactTypes as $type) {
                if (isset($result->{$type})) {
                    $contact = $result->{$type};
                    $contacts[mb_strtolower($type)] = [
                        'first_name' => (string) ($contact->FirstName ?? ''),
                        'last_name' => (string) ($contact->LastName ?? ''),
                        'organization' => (string) ($contact->OrganizationName ?? ''),
                        'address_one' => (string) ($contact->Address1 ?? ''),
                        'address_two' => (string) ($contact->Address2 ?? ''),
                        'city' => (string) ($contact->City ?? ''),
                        'state_province' => (string) ($contact->StateProvince ?? ''),
                        'postal_code' => (string) ($contact->PostalCode ?? ''),
                        'country_code' => (string) ($contact->Country ?? ''),
                        'phone' => (string) ($contact->Phone ?? ''),
                        'email' => (string) ($contact->EmailAddress ?? ''),
                    ];
                }
            }

            return [
                'success' => true,
                'domain' => $domain,
                'contacts' => $contacts,
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to get domain contacts: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Update domain contacts
     */
    public function updateDomainContacts(string $domain, array $contactInfo): array
    {
        try {
            $params = [
                'ApiUser' => $this->apiUser,
                'ApiKey' => $this->apiKey,
                'UserName' => $this->username,
                'ClientIp' => $this->clientIp,
                'Command' => 'namecheap.domains.setContacts',
                'DomainName' => $domain,
            ];

            // Add contact details
            $params = $this->addContactDetails($contactInfo, $params);

            $xml = $this->makeApiCall($params);

            if (! property_exists($xml->CommandResponse, 'DomainSetContactResult') || $xml->CommandResponse->DomainSetContactResult === null) {
                throw new Exception('Invalid API response: Missing DomainSetContactResult');
            }

            $result = $xml->CommandResponse->DomainSetContactResult;
            $isSuccess = mb_strtolower((string) $result['IsSuccess']) === 'true';

            if (! $isSuccess) {
                throw new Exception('Failed to update domain contacts');
            }

            return [
                'success' => true,
                'domain' => $domain,
                'message' => 'Domain contacts updated successfully',
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to update domain contacts: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Get EPP/Auth code for domain transfer
     */
    public function getAuthCode(string $domain): array
    {
        try {
            $params = [
                'ApiUser' => $this->apiUser,
                'ApiKey' => $this->apiKey,
                'UserName' => $this->username,
                'ClientIp' => $this->clientIp,
                'Command' => 'namecheap.domains.getEPPCode',
                'DomainName' => $domain,
            ];

            $xml = $this->makeApiCall($params);

            if (! property_exists($xml->CommandResponse, 'DomainGetEPPCodeResult') || $xml->CommandResponse->DomainGetEPPCodeResult === null) {
                throw new Exception('Invalid API response: Missing DomainGetEPPCodeResult');
            }

            $result = $xml->CommandResponse->DomainGetEPPCodeResult;
            $authCode = (string) ($result->EppCode ?? '');

            if ($authCode === '' || $authCode === '0') {
                throw new Exception('Failed to retrieve EPP code');
            }

            return [
                'success' => true,
                'domain' => $domain,
                'auth_code' => $authCode,
                'message' => 'EPP code retrieved successfully',
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to get EPP code: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Check domain transfer status
     */
    public function getTransferStatus(string $domain): array
    {
        try {
            $params = [
                'ApiUser' => $this->apiUser,
                'ApiKey' => $this->apiKey,
                'UserName' => $this->username,
                'ClientIp' => $this->clientIp,
                'Command' => 'namecheap.domains.transfer.getStatus',
                'DomainName' => $domain,
            ];

            $xml = $this->makeApiCall($params);

            if (! property_exists($xml->CommandResponse, 'DomainTransferGetStatusResult') || $xml->CommandResponse->DomainTransferGetStatusResult === null) {
                throw new Exception('Invalid API response: Missing DomainTransferGetStatusResult');
            }

            $result = $xml->CommandResponse->DomainTransferGetStatusResult;

            return [
                'success' => true,
                'domain' => $domain,
                'status' => (string) ($result->TransferStatus ?? 'Unknown'),
                'status_description' => (string) ($result->StatusDescription ?? ''),
                'transfer_date' => (string) ($result->TransferDate ?? ''),
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to get transfer status: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Resubmit domain transfer
     */
    public function resubmitTransfer(string $domain): array
    {
        try {
            $params = [
                'ApiUser' => $this->apiUser,
                'ApiKey' => $this->apiKey,
                'UserName' => $this->username,
                'ClientIp' => $this->clientIp,
                'Command' => 'namecheap.domains.transfer.resubmit',
                'DomainName' => $domain,
            ];

            $xml = $this->makeApiCall($params);

            if (! property_exists($xml->CommandResponse, 'DomainTransferResubmitResult') || $xml->CommandResponse->DomainTransferResubmitResult === null) {
                throw new Exception('Invalid API response: Missing DomainTransferResubmitResult');
            }

            $result = $xml->CommandResponse->DomainTransferResubmitResult;
            $isSuccess = mb_strtolower((string) $result['IsSuccess']) === 'true';

            if (! $isSuccess) {
                throw new Exception('Failed to resubmit domain transfer');
            }

            return [
                'success' => true,
                'domain' => $domain,
                'message' => 'Domain transfer resubmitted successfully',
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to resubmit transfer: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Get TLD list with pricing
     */
    public function getTldList(): array
    {
        try {
            $params = [
                'ApiUser' => $this->apiUser,
                'ApiKey' => $this->apiKey,
                'UserName' => $this->username,
                'ClientIp' => $this->clientIp,
                'Command' => 'namecheap.users.getPricing',
                'ProductType' => 'DOMAIN',
                'ActionName' => 'REGISTER',
            ];

            $xml = $this->makeApiCall($params);

            if (! property_exists($xml->CommandResponse, 'UserGetPricingResult') || $xml->CommandResponse->UserGetPricingResult === null) {
                throw new Exception('Invalid API response: Missing UserGetPricingResult');
            }

            $result = $xml->CommandResponse->UserGetPricingResult;
            $tlds = [];

            if (property_exists($result->ProductType->ProductCategory, 'Product') && $result->ProductType->ProductCategory->Product !== null) {
                foreach ($result->ProductType->ProductCategory->Product as $product) {
                    $tldName = (string) $product['Name'];
                    $prices = [];

                    if (property_exists($product, 'Price') && $product->Price !== null) {
                        foreach ($product->Price as $price) {
                            $duration = (int) $price['Duration'];
                            $prices[$duration] = [
                                'register' => (float) $price['YourPrice'],
                                'renew' => (float) ($price['RenewalPrice'] ?? $price['YourPrice']),
                                'transfer' => (float) ($price['TransferPrice'] ?? $price['YourPrice']),
                            ];
                        }
                    }

                    $tlds[] = [
                        'name' => $tldName,
                        'prices' => $prices,
                    ];
                }
            }

            return [
                'success' => true,
                'tlds' => $tlds,
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to get TLD list: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Add contact details to API parameters with proper validation
     *
     * @throws Exception
     */
    public function addContactDetails(array $contactInfo, array $params): array
    {
        $roleMapping = [
            'Registrant' => 'registrant',
            'Admin' => 'admin',
            'Tech' => 'technical',
            'AuxBilling' => 'billing',
        ];

        foreach ($roleMapping as $apiRole => $contactKey) {
            if (! isset($contactInfo[$contactKey])) {
                throw new Exception("Missing contact information for: $contactKey");
            }

            $contact = $contactInfo[$contactKey];

            // Ensure all required fields are present and not empty
            $params["{$apiRole}FirstName"] = mb_trim($contact['first_name'] ?? '');
            $params["{$apiRole}LastName"] = mb_trim($contact['last_name'] ?? '');
            $params["{$apiRole}Address1"] = mb_trim($contact['address_one'] ?? '');
            $params["{$apiRole}City"] = mb_trim($contact['city'] ?? '');
            $params["{$apiRole}StateProvince"] = mb_trim($contact['state_province'] ?? '');
            $params["{$apiRole}PostalCode"] = mb_trim($contact['postal_code'] ?? '');
            $params["{$apiRole}Country"] = mb_strtoupper(mb_trim($contact['country_code'] ?? ''));
            $params["{$apiRole}Phone"] = $this->formatPhoneNumber($contact['phone'] ?? '', $contact['country_code'] ?? '');
            $params["{$apiRole}EmailAddress"] = mb_trim($contact['email'] ?? '');
            $params["{$apiRole}OrganizationName"] = mb_trim($contact['organization'] ?? '');

            // Add optional address line 2 if provided
            if (! empty($contact['address_two'])) {
                $params["{$apiRole}Address2"] = mb_trim($contact['address_two']);
            }

            // Validate that none of the required fields are empty after trimming
            $requiredParams = [
                "{$apiRole}FirstName", "{$apiRole}LastName", "{$apiRole}Address1",
                "{$apiRole}City", "{$apiRole}StateProvince", "{$apiRole}PostalCode",
                "{$apiRole}Country", "{$apiRole}Phone", "{$apiRole}EmailAddress",
            ];

            foreach ($requiredParams as $param) {
                if (empty($params[$param])) {
                    throw new Exception("Empty required parameter: $param for contact role: $contactKey");
                }
            }
        }

        return $params;
    }

    /**
     * Get domain pricing
     */
    public function getDomainPricing(string $domain): array
    {
        $tld = $this->extractTld($domain);
        $domainPrice = DomainPrice::where('tld', $tld)->first();

        if (! $domainPrice) {
            return [
                'success' => false,
                'message' => 'Pricing not found for TLD: '.$tld,
            ];
        }

        return [
            'success' => true,
            'price' => $domainPrice->register_price / 100,
            'currency' => $domainPrice->type === DomainType::Local ? 'RWF' : 'USD',
            'is_premium' => $domainPrice->is_premium ?? false,
            'eap_fee' => 0,
        ];
    }

    /**
     * Suggest domains
     */
    public function suggestDomains(string $domain): array
    {
        try {
            $params = [
                'ApiUser' => $this->apiUser,
                'ApiKey' => $this->apiKey,
                'UserName' => $this->username,
                'ClientIp' => $this->clientIp,
                'Command' => 'namecheap.domains.getTldList',
            ];

            $xml = $this->makeApiCall($params);
            $tlds = [];

            if (property_exists($xml->CommandResponse, 'TldList') && $xml->CommandResponse->TldList !== null) {
                foreach ($xml->CommandResponse->TldList->Tld as $tld) {
                    $tlds[] = (string) $tld['Name'];
                }
            }

            // Get availability for suggested domains
            $suggestedDomains = array_map(function (string $tld) use ($domain): string {
                return $domain.'.'.$tld;
            }, array_slice($tlds, 0, 10));

            return $this->checkAvailability($suggestedDomains);

        } catch (Exception) {
            return [];
        }
    }

    /**
     * Create contact
     *
     * @throws Exception
     */
    public function createContact(array $contactData): Contact
    {
        if (empty($contactData['email'])) {
            throw new Exception('Email is required for contact creation');
        }

        // Format phone number before saving
        $contactData['phone'] = $this->formatPhoneNumber($contactData['phone'] ?? '', $contactData['country_code'] ?? '');

        // Generate a unique contact ID
        $contactId = 'NC'.mb_strtoupper(Str::random(8));

        // Map contact data to database fields
        $dbData = [
            'uuid' => (string) Str::uuid(),
            'contact_id' => $contactId,
            'provider' => 'namecheap',
            'contact_type' => $contactData['contact_type'] ?? 'registrant',
            'first_name' => $contactData['first_name'] ?? $this->extractFirstName($contactData['name'] ?? ''),
            'last_name' => $contactData['last_name'] ?? $this->extractLastName($contactData['name'] ?? ''),
            'title' => $contactData['title'] ?? null,
            'organization' => $contactData['organization'] ?? null,
            'address_one' => $contactData['address_one'] ?? '',
            'address_two' => $contactData['address_two'] ?? null,
            'city' => $contactData['city'] ?? '',
            'state_province' => $contactData['state_province'] ?? '',
            'postal_code' => $contactData['postal_code'] ?? '',
            'country_code' => $contactData['country_code'] ?? null,
            'phone' => $contactData['phone'],
            'phone_extension' => $contactData['phone_extension'] ?? null,
            'fax_number' => $contactData['fax'] ?? null,
            'email' => $contactData['email'],
            'user_id' => $contactData['user_id'] ?? auth()->id(),
        ];

        return Contact::create($dbData);
    }

    /**
     * Create contacts in the registry (Namecheap doesn't have separate contact registry)
     * This method is required by the DomainRegistrationServiceInterface
     *
     * @throws Exception
     */
    public function createContacts(array $contactData): array
    {
        // For Namecheap, we just return the contact_id as-is since there's no separate contact registry
        // The contact is created in the local database and used directly in domain operations
        return [
            'contact_id' => $contactData['contact_id'] ?? 'NC'.mb_strtoupper(Str::random(8)),
            'success' => true,
            'message' => 'Contact created successfully in Namecheap system',
        ];
    }

    /**
     * Update domain nameservers
     */
    public function updateNameservers(string $domain, array $nameservers): array
    {
        try {
            $params = [
                'ApiUser' => $this->apiUser,
                'ApiKey' => $this->apiKey,
                'UserName' => $this->username,
                'ClientIp' => $this->clientIp,
                'Command' => 'namecheap.domains.dns.setCustom',
                'SLD' => $this->extractSld($domain),
                'TLD' => mb_ltrim($this->extractTld($domain), '.'),
                'Nameservers' => implode(',', $nameservers),
            ];

            $this->makeApiCall($params);

            return [
                'success' => true,
                'message' => 'Nameservers updated successfully',
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to update nameservers: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Get domain nameservers
     */
    public function getNameservers(string $domain): array
    {
        try {
            $params = [
                'ApiUser' => $this->apiUser,
                'ApiKey' => $this->apiKey,
                'UserName' => $this->username,
                'ClientIp' => $this->clientIp,
                'Command' => 'namecheap.domains.dns.getList',
                'SLD' => $this->extractSld($domain),
                'TLD' => mb_ltrim($this->extractTld($domain), '.'),
            ];

            $xml = $this->makeApiCall($params);

            $nameservers = [];
            if (property_exists($xml->CommandResponse->DomainDNSGetListResult, 'Nameserver') && $xml->CommandResponse->DomainDNSGetListResult->Nameserver !== null) {
                $nsList = $xml->CommandResponse->DomainDNSGetListResult->Nameserver;
                foreach ($nsList as $ns) {
                    $nameservers[] = (string) $ns;
                }
            }

            return [
                'success' => true,
                'nameservers' => $nameservers,
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to get nameservers: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Get domain information
     */
    public function getDomainInfo(string $domain): array
    {
        try {
            $params = [
                'ApiUser' => $this->apiUser,
                'ApiKey' => $this->apiKey,
                'UserName' => $this->username,
                'ClientIp' => $this->clientIp,
                'Command' => 'namecheap.domains.getInfo',
                'DomainName' => $domain,
            ];

            $xml = $this->makeApiCall($params);

            if (! property_exists($xml->CommandResponse, 'DomainGetInfoResult') || $xml->CommandResponse->DomainGetInfoResult === null) {
                throw new Exception('Invalid API response: Missing DomainGetInfoResult');
            }

            $domainInfo = $xml->CommandResponse->DomainGetInfoResult;

            return [
                'success' => true,
                'domain' => (string) $domainInfo['Name'],
                'status' => [(string) $domainInfo['Status']],
                'registrant' => (string) ($domainInfo->Registrant ?? ''),
                'created_date' => (string) ($domainInfo->CreatedDate ?? ''),
                'expiry_date' => (string) ($domainInfo->ExpiredDate ?? ''),
                'locked' => mb_strtolower((string) ($domainInfo->Locked ?? 'false')) === 'true',
                'whoisguard_enabled' => mb_strtolower((string) ($domainInfo->WhoisguardEnabled ?? 'false')) === 'true',
                'auto_renew' => mb_strtolower((string) ($domainInfo->AutoRenew ?? 'false')) === 'true',
            ];

        } catch (Exception $e) {
            Log::error('Namecheap Domain Info API Error:', [
                'domain' => $domain,
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Renew a domain registration
     */
    public function renewDomainRegistration(string $domain, int $years): array
    {
        try {
            $params = [
                'ApiUser' => $this->apiUser,
                'ApiKey' => $this->apiKey,
                'UserName' => $this->username,
                'ClientIp' => $this->clientIp,
                'Command' => 'namecheap.domains.renew',
                'DomainName' => $domain,
                'Years' => $years,
            ];

            $xml = $this->makeApiCall($params);

            if (! property_exists($xml->CommandResponse, 'DomainRenewResult') || $xml->CommandResponse->DomainRenewResult === null) {
                throw new Exception('Invalid API response: Missing DomainRenewResult');
            }

            $renewalResult = $xml->CommandResponse->DomainRenewResult;

            return [
                'success' => true,
                'domain' => $domain,
                'expiry_date' => (string) ($renewalResult['RenewedUntil'] ?? ''),
                'message' => 'Domain renewed successfully',
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to renew domain: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Transfer a domain from another registrar
     */
    public function transferDomainRegistration(string $domain, string $authCode, array $contactInfo): array
    {
        try {
            $params = [
                'ApiUser' => $this->apiUser,
                'ApiKey' => $this->apiKey,
                'UserName' => $this->username,
                'ClientIp' => $this->clientIp,
                'Command' => 'namecheap.domains.transfer',
                'DomainName' => $domain,
                'AuthCode' => $authCode,
            ];

            // Add contact details
            $params = $this->addContactDetails($contactInfo, $params);

            $this->makeApiCall($params);

            return [
                'success' => true,
                'domain' => $domain,
                'message' => 'Domain transfer initiated successfully',
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to initiate domain transfer: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Get domain list for a user
     */
    public function getDomainList(int $page = 1, int $pageSize = 20): array
    {
        try {
            $params = [
                'ApiUser' => $this->apiUser,
                'ApiKey' => $this->apiKey,
                'UserName' => $this->username,
                'ClientIp' => $this->clientIp,
                'Command' => 'namecheap.domains.getList',
                'Page' => $page,
                'PageSize' => $pageSize,
            ];

            $xml = $this->makeApiCall($params);

            $domains = [];
            if (property_exists($xml->CommandResponse->DomainGetListResult, 'Domain') && $xml->CommandResponse->DomainGetListResult->Domain !== null) {
                $domainList = $xml->CommandResponse->DomainGetListResult->Domain;
                foreach ($domainList as $domain) {
                    $domains[] = [
                        'name' => (string) $domain['Name'],
                        'created_date' => (string) ($domain['Created'] ?? ''),
                        'expiry_date' => (string) ($domain['Expires'] ?? ''),
                        'status' => (string) ($domain['Status'] ?? ''),
                        'auto_renew' => mb_strtolower((string) ($domain['AutoRenew'] ?? 'false')) === 'true',
                        'locked' => mb_strtolower((string) ($domain['IsLocked'] ?? 'false')) === 'true',
                        'whoisguard_enabled' => mb_strtolower((string) ($domain['WhoisguardEnabled'] ?? 'false')) === 'true',
                    ];
                }
            }

            $totalItems = 0;
            if (property_exists($xml->CommandResponse->Paging, 'TotalItems') && $xml->CommandResponse->Paging->TotalItems !== null) {
                $totalItems = (int) $xml->CommandResponse->Paging->TotalItems;
            }

            return [
                'success' => true,
                'domains' => $domains,
                'total' => $totalItems,
                'page' => $page,
                'page_size' => $pageSize,
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to get domain list: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Set domain auto-renewal status
     */
    public function setAutoRenew(string $domain, bool $autoRenew): array
    {
        try {
            $params = [
                'ApiUser' => $this->apiUser,
                'ApiKey' => $this->apiKey,
                'UserName' => $this->username,
                'ClientIp' => $this->clientIp,
                'Command' => 'namecheap.domains.setAutoRenew',
                'DomainName' => $domain,
                'AutoRenew' => $autoRenew ? 'true' : 'false',
            ];

            $xml = $this->makeApiCall($params);

            if (! property_exists($xml->CommandResponse, 'DomainSetAutoRenewResult') || $xml->CommandResponse->DomainSetAutoRenewResult === null) {
                throw new Exception('Invalid API response: Missing DomainSetAutoRenewResult');
            }

            $result = $xml->CommandResponse->DomainSetAutoRenewResult;
            $isSuccess = mb_strtolower((string) $result['IsSuccess']) === 'true';

            if (! $isSuccess) {
                throw new Exception('Failed to update auto-renewal status');
            }

            return [
                'success' => true,
                'domain' => $domain,
                'auto_renew' => $autoRenew,
                'message' => $autoRenew ? 'Auto-renewal enabled' : 'Auto-renewal disabled',
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to set auto-renewal: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Get user balance
     */
    public function getUserBalance(): array
    {
        try {
            $params = [
                'ApiUser' => $this->apiUser,
                'ApiKey' => $this->apiKey,
                'UserName' => $this->username,
                'ClientIp' => $this->clientIp,
                'Command' => 'namecheap.users.getBalances',
            ];

            $xml = $this->makeApiCall($params);

            if (! property_exists($xml->CommandResponse, 'UserGetBalancesResult') || $xml->CommandResponse->UserGetBalancesResult === null) {
                throw new Exception('Invalid API response: Missing UserGetBalancesResult');
            }

            $result = $xml->CommandResponse->UserGetBalancesResult;

            return [
                'success' => true,
                'account_balance' => (float) ($result['AccountBalance'] ?? 0),
                'available_balance' => (float) ($result['AvailableBalance'] ?? 0),
                'currency' => 'USD',
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to get user balance: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Validate domain for transfer
     */
    public function validateDomainForTransfer(string $domain): array
    {
        try {
            // Check if domain exists and get info
            $domainInfo = $this->getDomainInfo($domain);

            if (! $domainInfo['success']) {
                return [
                    'success' => false,
                    'message' => 'Domain not found or invalid',
                    'eligible' => false,
                ];
            }

            // Check if domain is older than 60 days (ICANN requirement)
            $createdDate = strtotime($domainInfo['created_date']);
            $sixtyDaysAgo = strtotime('-60 days');

            if ($createdDate > $sixtyDaysAgo) {
                return [
                    'success' => true,
                    'eligible' => false,
                    'message' => 'Domain must be at least 60 days old before transfer',
                    'created_date' => $domainInfo['created_date'],
                ];
            }

            // Check if domain is locked
            $locked = $domainInfo['locked'] ?? false;

            return [
                'success' => true,
                'eligible' => true,
                'locked' => $locked,
                'message' => $locked ? 'Domain is locked - must be unlocked before transfer' : 'Domain is eligible for transfer',
                'created_date' => $domainInfo['created_date'],
                'expiry_date' => $domainInfo['expiry_date'],
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to validate domain for transfer: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Get WhoisGuard ID for a domain
     *
     * @throws Exception
     */
    private function getWhoisGuardId(string $domain): string
    {
        $domainInfo = $this->getDomainInfo($domain);

        if (! $domainInfo['success']) {
            throw new Exception('Failed to get domain information');
        }

        // This would need to be extracted from domain info
        // For now, return a placeholder - you'd need to modify getDomainInfo to include WhoisGuard ID
        return 'WG_'.mb_strtoupper(str_replace('.', '_', $domain));
    }

    /**
     * Validate contact information
     *
     * @throws Exception
     */
    private function validateContactInfo(array $contactInfo): void
    {
        $requiredRoles = ['registrant', 'admin', 'technical', 'billing'];
        $requiredFields = ['first_name', 'last_name', 'address_one', 'city', 'state_province', 'postal_code', 'country_code', 'phone', 'email'];

        foreach ($requiredRoles as $role) {
            if (! isset($contactInfo[$role])) {
                throw new Exception("Missing contact information for role: $role");
            }

            $contact = $contactInfo[$role];
            foreach ($requiredFields as $field) {
                if (empty($contact[$field])) {
                    throw new Exception("Missing required field '$field' for $role contact");
                }
            }

            // Validate email format
            if (! filter_var($contact['email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid email format for $role contact: ".$contact['email']);
            }

            // Validate country code
            if (mb_strlen($contact['country_code']) !== 2) {
                throw new Exception("Invalid country code for $role contact. Must be 2-letter ISO code: ".$contact['country_code']);
            }
        }
    }

    /**
     * Make API call with proper error handling
     *
     * @throws Exception
     */
    private function makeApiCall(array $params): SimpleXMLElement
    {
        $query = http_build_query($params);
        $url = $this->apiBaseUrl.'?'.$query;

        try {
            $response = Http::timeout(60)->get($url);
        } catch (ConnectionException $e) {
            Log::error('Namecheap API connection failed', [
                'error' => $e->getMessage(),
                'url' => $this->apiBaseUrl,
            ]);
            throw new Exception('Failed to connect to Namecheap API: '.$e->getMessage(), $e->getCode(), $e);
        }

        if (! $response->successful()) {
            Log::error('Namecheap API request failed', [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);
            throw new Exception('API request failed with HTTP status: '.$response->status());
        }

        $responseBody = $response->body();
        if (empty($responseBody)) {
            throw new Exception('Empty response from Namecheap API');
        }

        // Log the full response for debugging
        Log::info('Namecheap API Response', [
            'response_body' => $responseBody,
        ]);

        try {
            $xml = new SimpleXMLElement($responseBody);
        } catch (Exception $e) {
            Log::error('Failed to parse Namecheap API XML response', [
                'body' => $responseBody,
                'error' => $e->getMessage(),
            ]);
            throw new Exception('Invalid XML response from Namecheap API: '.$e->getMessage(), $e->getCode(), $e);
        }

        // Check for API-level errors
        if (property_exists($xml, 'Errors') && $xml->Errors !== null && $xml->Errors->count() > 0) {
            $errors = [];
            foreach ($xml->Errors->Error as $error) {
                $errors[] = (string) $error;
            }

            if ($errors !== []) {
                $errorMessage = implode(', ', $errors);
                Log::error('Namecheap API returned errors', [
                    'errors' => $errors,
                    'response' => $responseBody,
                ]);
                throw new Exception('Namecheap API error: '.$errorMessage);
            }
        }

        // Check status attribute
        if (isset($xml['Status']) && mb_strtolower((string) $xml['Status']) === 'error') {
            $errorMsg = 'API returned error status';
            if (property_exists($xml, 'Errors') && $xml->Errors !== null) {
                $errorMsg = (string) $xml->Errors->Error ?? $errorMsg;
            }
            throw new Exception($errorMsg);
        }

        return $xml;
    }

    /**
     * Format phone number to Namecheap API format (+<country_code>.<number>)
     *
     * @throws Exception
     */
    private function formatPhoneNumber(string $phone, string $countryCode): string
    {
        if ($phone === '' || $phone === '0') {
            throw new Exception('Phone number is required');
        }

        // Remove any non-digit characters except the leading +
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        // Remove leading zeros from the phone number part
        $phone = mb_ltrim($phone, '0');

        // Country calling codes mapping
        $countryCallingCodes = [
            'US' => '1', 'CA' => '1', 'RW' => '250', 'GB' => '44', 'DE' => '49',
            'FR' => '33', 'AU' => '61', 'JP' => '81', 'CN' => '86', 'IN' => '91',
            'BR' => '55', 'ZA' => '27', 'NG' => '234', 'KE' => '254', 'GH' => '233',
            'UG' => '256', 'TZ' => '255', 'ET' => '251', 'ZW' => '263', 'ZM' => '260',
        ];

        $countryCode = mb_strtoupper(mb_trim($countryCode));
        $callingCode = $countryCallingCodes[$countryCode] ?? '1'; // Default to US

        // If phone already starts with +, remove it
        if (str_starts_with($phone, '+')) {
            $phone = mb_substr($phone, 1);
        }

        // If phone starts with country code, remove it to avoid duplication
        if (str_starts_with($phone, $callingCode)) {
            $phone = mb_substr($phone, mb_strlen($callingCode));
        }

        // Remove any remaining leading zeros
        $phone = mb_ltrim($phone, '0');

        if ($phone === '' || $phone === '0') {
            throw new Exception('Invalid phone number format');
        }

        // Format according to Namecheap requirements: +NNN.NNNNNNNNNN
        return '+'.$callingCode.'.'.$phone;
    }

    /**
     * Get TLD-specific parameters
     */
    private function getTldSpecificParams(string $tld): array
    {
        $params = [];

        switch (mb_strtolower($tld)) {
            case '.us':
                $params['NexusCategory'] = 'C11'; // Individual US citizen
                $params['NexusPurpose'] = 'P1'; // Business use
                break;
            case '.ca':
                $params['LegalType'] = 'CCT'; // Canadian Citizen
                $params['CIRAAgreementVersion'] = '2.0';
                break;
            case '.eu':
            case '.asia':
            case '.uk':
            case '.co.uk':
                // UK domains may require additional parameters
                break;
        }

        return $params;
    }

    /**
     * Validate domain name
     */
    private function isValidDomainName(string $domain): bool
    {
        return filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false;
    }

    /**
     * Extract TLD from domain
     */
    private function extractTld(string $domain): string
    {
        $parts = explode('.', $domain);

        return '.'.end($parts);
    }

    /**
     * Extract SLD from domain
     */
    private function extractSld(string $domain): string
    {
        $parts = explode('.', $domain);

        return $parts[0];
    }

    /**
     * Extract first name from full name
     */
    private function extractFirstName(string $fullName): string
    {
        $parts = explode(' ', mb_trim($fullName));

        return $parts[0] ?? '';
    }

    /**
     * Extract last name from full name
     */
    private function extractLastName(string $fullName): string
    {
        $parts = explode(' ', mb_trim($fullName));
        array_shift($parts);

        return implode(' ', $parts);
    }
}
