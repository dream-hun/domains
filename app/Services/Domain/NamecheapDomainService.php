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

class NamecheapDomainService implements DomainRegistrationServiceInterface, DomainServiceInterface
{
    private readonly string $apiUser;

    private readonly string $apiKey;

    private readonly string $username;

    private readonly string $clientIp;

    private readonly string $apiBaseUrl;

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

            throw_if(empty($response->body()), Exception::class, 'Namecheap API error: Empty response from API.');

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
                throw_if($errorMsg !== '' && $errorMsg !== '0', Exception::class, 'Namecheap API error: '.$errorMsg);
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
            throw_if(! property_exists($xml->CommandResponse, 'DomainCheckResult') || $xml->CommandResponse->DomainCheckResult === null, Exception::class, 'Namecheap API error: Missing DomainCheckResult in response.');

            $domainCheckResults = $xml->CommandResponse->DomainCheckResult;

            foreach ($domainCheckResults as $result) {
                $domainName = (string) $result['Domain'];
                $available = mb_strtolower((string) $result['Available']) === 'true';

                // Handle various error scenarios
                $errorMessage = null;
                if (! $available) {
                    $errorMessage = (string) $result['ErrorMessage'];
                    if ($errorMessage === '' || $errorMessage === '0') {
                        $errorMessage = 'Domain not available';
                    }
                }

                // Detect premium flags and pricing if provided by Namecheap
                // Attribute names vary across docs; support common variants
                $isPremium = false;
                $premiumPrice = null;
                $eapFee = 0.0;

                $premiumFlags = [
                    'IsPremiumName',
                    'IsPremium',
                    'PremiumDomain',
                ];
                foreach ($premiumFlags as $flag) {
                    if (isset($result[$flag]) && mb_strtolower((string) $result[$flag]) === 'true') {
                        $isPremium = true;
                        break;
                    }
                }

                $priceKeys = [
                    'PremiumRegistrationPrice',
                    'PremiumPrice',
                    'PremiumRegistrationCost',
                ];
                foreach ($priceKeys as $key) {
                    if (isset($result[$key]) && (string) $result[$key] !== '') {
                        $premiumPrice = (float) $result[$key];
                        break;
                    }
                }

                // EAP fee if present
                if (isset($result['EapFee']) && (string) $result['EapFee'] !== '') {
                    $eapFee = (float) $result['EapFee'];
                }

                Log::debug('Namecheap domain check result', [
                    'domain' => $domainName,
                    'available' => $available,
                    'is_premium' => $isPremium,
                    'premium_price' => $premiumPrice,
                    'eap_fee' => $eapFee,
                    'raw_available' => (string) $result['Available'],
                    'error_message' => $errorMessage,
                ]);

                $results[$domainName] = (object) [
                    'available' => $available,
                    'reason' => $errorMessage,
                    'error' => $errorMessage,
                    'is_premium' => $isPremium,
                    'premium_price' => $premiumPrice,
                    'eap_fee' => $eapFee,
                ];
            }

            return $results;
        } catch (Exception $exception) {
            Log::error('Namecheap domain check error', [
                'domains' => $domain,
                'message' => $exception->getMessage(),
            ]);
            $results = [];
            foreach ($domain as $d) {
                $serviceError = 'Service error: '.$exception->getMessage();

                $results[$d] = (object) [
                    'available' => false,
                    'reason' => $serviceError,
                    'error' => $exception->getMessage(),
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
            throw_unless($this->isValidDomainName($domain), Exception::class, 'Invalid domain name format');

            // Validate registration years
            throw_if($years < 1 || $years > 10, Exception::class, 'Registration years must be between 1 and 10');

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

            $params = $this->addContactDetails($contactInfo, $params);
            $tld = $this->extractTld($domain);
            $params = array_merge($params, $this->getTldSpecificParams($tld));

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
            // Add premium domain pricing if applicable

            // Make API call
            $xml = $this->makeApiCall($params);

            // Validate successful registration
            throw_if(! property_exists($xml->CommandResponse, 'DomainCreateResult') || $xml->CommandResponse->DomainCreateResult === null, Exception::class, 'Invalid API response: Missing DomainCreateResult');

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

        } catch (Exception $exception) {
            Log::error('Namecheap domain registration failed', [
                'domain' => $domain,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => $exception->getMessage(),
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

            throw_if(! property_exists($xml->CommandResponse, 'DomainSetRegistrarLockResult') || $xml->CommandResponse->DomainSetRegistrarLockResult === null, Exception::class, 'Invalid API response: Missing DomainSetRegistrarLockResult');

            $result = $xml->CommandResponse->DomainSetRegistrarLockResult;
            $isSuccess = mb_strtolower((string) $result['IsSuccess']) === 'true';

            throw_unless($isSuccess, Exception::class, 'Failed to update domain lock status');

            return [
                'success' => true,
                'domain' => $domain,
                'locked' => $lock,
                'message' => $lock ? 'Domain locked successfully' : 'Domain unlocked successfully',
            ];

        } catch (Exception $exception) {
            return [
                'success' => false,
                'message' => 'Failed to update domain lock: '.$exception->getMessage(),
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

            throw_if(! property_exists($xml->CommandResponse, 'DomainGetRegistrarLockResult') || $xml->CommandResponse->DomainGetRegistrarLockResult === null, Exception::class, 'Invalid API response: Missing DomainGetRegistrarLockResult');

            $result = $xml->CommandResponse->DomainGetRegistrarLockResult;
            $locked = mb_strtolower((string) $result['RegistrarLockStatus']) === 'true';

            return [
                'success' => true,
                'domain' => $domain,
                'locked' => $locked,
            ];

        } catch (Exception $exception) {
            return [
                'success' => false,
                'message' => 'Failed to get domain lock status: '.$exception->getMessage(),
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

            throw_if(! property_exists($xml->CommandResponse, 'WhoisguardEnableResult') || $xml->CommandResponse->WhoisguardEnableResult === null, Exception::class, 'Invalid API response: Missing WhoisguardEnableResult');

            $result = $xml->CommandResponse->WhoisguardEnableResult;
            $isSuccess = mb_strtolower((string) $result['IsSuccess']) === 'true';

            throw_unless($isSuccess, Exception::class, 'Failed to enable WhoisGuard');

            return [
                'success' => true,
                'domain' => $domain,
                'message' => 'WhoisGuard enabled successfully',
            ];

        } catch (Exception $exception) {
            return [
                'success' => false,
                'message' => 'Failed to enable WhoisGuard: '.$exception->getMessage(),
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

            throw_if(! property_exists($xml->CommandResponse, 'WhoisguardDisableResult') || $xml->CommandResponse->WhoisguardDisableResult === null, Exception::class, 'Invalid API response: Missing WhoisguardDisableResult');

            $result = $xml->CommandResponse->WhoisguardDisableResult;
            $isSuccess = mb_strtolower((string) $result['IsSuccess']) === 'true';

            throw_unless($isSuccess, Exception::class, 'Failed to disable WhoisGuard');

            return [
                'success' => true,
                'domain' => $domain,
                'message' => 'WhoisGuard disabled successfully',
            ];

        } catch (Exception $exception) {
            return [
                'success' => false,
                'message' => 'Failed to disable WhoisGuard: '.$exception->getMessage(),
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

            throw_if(! property_exists($xml->CommandResponse, 'DomainContactsResult') || $xml->CommandResponse->DomainContactsResult === null, Exception::class, 'Invalid API response: Missing DomainContactsResult');

            $result = $xml->CommandResponse->DomainContactsResult;
            $contacts = [];

            // Map API contact types to our internal naming convention
            $contactTypeMapping = [
                'Registrant' => 'registrant',
                'Admin' => 'admin',
                'Tech' => 'tech',
                'AuxBilling' => 'auxbilling',
            ];

            foreach ($contactTypeMapping as $apiType => $internalType) {
                if (isset($result->{$apiType})) {
                    $contact = $result->{$apiType};

                    // Normalize contact data structure
                    $contacts[$internalType] = [
                        'first_name' => mb_trim((string) ($contact->FirstName ?? '')),
                        'last_name' => mb_trim((string) ($contact->LastName ?? '')),
                        'organization' => mb_trim((string) ($contact->OrganizationName ?? '')),
                        'address_one' => mb_trim((string) ($contact->Address1 ?? '')),
                        'address_two' => mb_trim((string) ($contact->Address2 ?? '')),
                        'city' => mb_trim((string) ($contact->City ?? '')),
                        'state_province' => mb_trim((string) ($contact->StateProvince ?? '')),
                        'postal_code' => mb_trim((string) ($contact->PostalCode ?? '')),
                        'country_code' => mb_strtoupper(mb_trim((string) ($contact->Country ?? ''))),
                        'phone' => mb_trim((string) ($contact->Phone ?? '')),
                        'email' => mb_trim((string) ($contact->EmailAddress ?? '')),
                    ];

                    Log::debug(sprintf("Retrieved contact for type '%s'", $internalType), [
                        'domain' => $domain,
                        'type' => $internalType,
                        'email' => $contacts[$internalType]['email'],
                        'name' => $contacts[$internalType]['first_name'].' '.$contacts[$internalType]['last_name'],
                    ]);
                }
            }

            throw_if($contacts === [], Exception::class, 'No contact information found for domain');

            Log::info('Successfully retrieved domain contacts', [
                'domain' => $domain,
                'contact_types' => array_keys($contacts),
                'total_contacts' => count($contacts),
            ]);

            return [
                'success' => true,
                'domain' => $domain,
                'contacts' => $contacts,
            ];

        } catch (Exception $exception) {
            Log::error('Failed to get domain contacts from Namecheap', [
                'domain' => $domain,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to get domain contacts: '.$exception->getMessage(),
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

            throw_if(! property_exists($xml->CommandResponse, 'DomainSetContactResult') || $xml->CommandResponse->DomainSetContactResult === null, Exception::class, 'Invalid API response: Missing DomainSetContactResult');

            $result = $xml->CommandResponse->DomainSetContactResult;
            $isSuccess = mb_strtolower((string) $result['IsSuccess']) === 'true';

            throw_unless($isSuccess, Exception::class, 'Failed to update domain contacts');

            return [
                'success' => true,
                'domain' => $domain,
                'message' => 'Domain contacts updated successfully',
            ];

        } catch (Exception $exception) {
            return [
                'success' => false,
                'message' => 'Failed to update domain contacts: '.$exception->getMessage(),
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

            throw_if(! property_exists($xml->CommandResponse, 'DomainGetEPPCodeResult') || $xml->CommandResponse->DomainGetEPPCodeResult === null, Exception::class, 'Invalid API response: Missing DomainGetEPPCodeResult');

            $result = $xml->CommandResponse->DomainGetEPPCodeResult;
            $authCode = (string) ($result->EppCode ?? '');

            throw_if($authCode === '' || $authCode === '0', Exception::class, 'Failed to retrieve EPP code');

            return [
                'success' => true,
                'domain' => $domain,
                'auth_code' => $authCode,
                'message' => 'EPP code retrieved successfully',
            ];

        } catch (Exception $exception) {
            return [
                'success' => false,
                'message' => 'Failed to get EPP code: '.$exception->getMessage(),
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

            throw_if(! property_exists($xml->CommandResponse, 'DomainTransferGetStatusResult') || $xml->CommandResponse->DomainTransferGetStatusResult === null, Exception::class, 'Invalid API response: Missing DomainTransferGetStatusResult');

            $result = $xml->CommandResponse->DomainTransferGetStatusResult;

            return [
                'success' => true,
                'domain' => $domain,
                'status' => (string) ($result->TransferStatus ?? 'Unknown'),
                'status_description' => (string) ($result->StatusDescription ?? ''),
                'transfer_date' => (string) ($result->TransferDate ?? ''),
            ];

        } catch (Exception $exception) {
            return [
                'success' => false,
                'message' => 'Failed to get transfer status: '.$exception->getMessage(),
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

            throw_if(! property_exists($xml->CommandResponse, 'DomainTransferResubmitResult') || $xml->CommandResponse->DomainTransferResubmitResult === null, Exception::class, 'Invalid API response: Missing DomainTransferResubmitResult');

            $result = $xml->CommandResponse->DomainTransferResubmitResult;
            $isSuccess = mb_strtolower((string) $result['IsSuccess']) === 'true';

            throw_unless($isSuccess, Exception::class, 'Failed to resubmit domain transfer');

            return [
                'success' => true,
                'domain' => $domain,
                'message' => 'Domain transfer resubmitted successfully',
            ];

        } catch (Exception $exception) {
            return [
                'success' => false,
                'message' => 'Failed to resubmit transfer: '.$exception->getMessage(),
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

            throw_if(! property_exists($xml->CommandResponse, 'UserGetPricingResult') || $xml->CommandResponse->UserGetPricingResult === null, Exception::class, 'Invalid API response: Missing UserGetPricingResult');

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

        } catch (Exception $exception) {
            return [
                'success' => false,
                'message' => 'Failed to get TLD list: '.$exception->getMessage(),
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
            throw_unless(isset($contactInfo[$contactKey]), Exception::class, 'Missing contact information for: '.$contactKey);

            $contact = $contactInfo[$contactKey];

            // Ensure all required fields are present and not empty
            $params[$apiRole.'FirstName'] = mb_trim($contact['first_name'] ?? '');
            $params[$apiRole.'LastName'] = mb_trim($contact['last_name'] ?? '');
            $params[$apiRole.'Address1'] = mb_trim($contact['address_one'] ?? '');
            $params[$apiRole.'City'] = mb_trim($contact['city'] ?? '');
            $params[$apiRole.'StateProvince'] = mb_trim($contact['state_province'] ?? '');
            $params[$apiRole.'PostalCode'] = mb_trim($contact['postal_code'] ?? '');
            $params[$apiRole.'Country'] = mb_strtoupper(mb_trim($contact['country_code'] ?? ''));
            $params[$apiRole.'Phone'] = $this->formatPhoneNumber($contact['phone'] ?? '', $contact['country_code'] ?? '');
            $params[$apiRole.'EmailAddress'] = mb_trim($contact['email'] ?? '');
            $params[$apiRole.'OrganizationName'] = mb_trim($contact['organization'] ?? '');

            // Add optional address line 2 if provided
            if (! empty($contact['address_two'])) {
                $params[$apiRole.'Address2'] = mb_trim($contact['address_two']);
            }

            // Validate that none of the required fields are empty after trimming
            $requiredParams = [
                $apiRole.'FirstName', $apiRole.'LastName', $apiRole.'Address1',
                $apiRole.'City', $apiRole.'StateProvince', $apiRole.'PostalCode',
                $apiRole.'Country', $apiRole.'Phone', $apiRole.'EmailAddress',
            ];

            foreach ($requiredParams as $param) {
                throw_if(empty($params[$param]), Exception::class, sprintf('Empty required parameter: %s for contact role: %s', $param, $contactKey));
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
        $domainPrice = DomainPrice::query()->where('tld', $tld)->first();

        if (! $domainPrice) {
            return [
                'success' => false,
                'message' => 'Pricing not found for TLD: '.$tld,
            ];
        }

        // Check if domain is premium by querying Namecheap API
        try {
            $availability = $this->checkAvailability([$domain]);

            if (isset($availability[$domain])) {
                $domainInfo = (array) $availability[$domain];

                // If it's a premium domain, return the premium price
                if (($domainInfo['is_premium'] ?? false) === true) {
                    return [
                        'success' => true,
                        'price' => (float) ($domainInfo['premium_price'] ?? ($domainPrice->register_price / 100)),
                        'currency' => $domainPrice->type === DomainType::Local ? 'RWF' : 'USD',
                        'is_premium' => true,
                        'eap_fee' => (float) ($domainInfo['eap_fee'] ?? 0),
                    ];
                }
            }
        } catch (Exception $exception) {
            Log::warning('Failed to check premium pricing for domain', [
                'domain' => $domain,
                'error' => $exception->getMessage(),
            ]);
        }

        // Return standard pricing
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
            $suggestedDomains = array_map(fn (string $tld): string => $domain.'.'.$tld, array_slice($tlds, 0, 10));

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
        throw_if(empty($contactData['email']), Exception::class, 'Email is required for contact creation');

        // Format phone number before saving
        $contactData['phone'] = $this->formatPhoneNumber($contactData['phone'] ?? '', $contactData['country_code'] ?? '');

        // Generate a unique contact ID
        $contactId = 'NC'.mb_strtoupper(Str::random(8));

        // Map contact data to database fields
        $dbData = [
            'uuid' => (string) Str::uuid(),
            'contact_id' => $contactId,
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

        return Contact::query()->create($dbData);
    }

    /**
     * Create contacts in the registry (Namecheap doesn't have separate contact registry)
     * This method is required by the DomainRegistrationServiceInterface
     *
     * @throws Exception
     */
    public function createContacts(array $contactData): array
    {
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
                'Command' => 'namecheap.domains.dns.setDefault',
                'SLD' => $this->extractSld($domain),
                'TLD' => mb_ltrim($this->extractTld($domain), '.'),
                'Nameservers' => implode(',', $nameservers),
            ];

            $this->makeApiCall($params);

            return [
                'success' => true,
                'message' => 'Nameservers updated successfully',
            ];

        } catch (Exception $exception) {
            return [
                'success' => false,
                'message' => 'Failed to update nameservers: '.$exception->getMessage(),
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

        } catch (Exception $exception) {
            return [
                'success' => false,
                'message' => 'Failed to get nameservers: '.$exception->getMessage(),
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

            throw_if(! property_exists($xml->CommandResponse, 'DomainGetInfoResult') || $xml->CommandResponse->DomainGetInfoResult === null, Exception::class, 'Invalid API response: Missing DomainGetInfoResult');

            $domainInfo = $xml->CommandResponse->DomainGetInfoResult;
            $dnsDetails = $domainInfo->DnsDetails;
            $domainDetails = $domainInfo->DomainDetails;

            // Extract nameservers if they exist
            $nameservers = [];
            if (property_exists($dnsDetails, 'Nameserver')) {
                foreach ($dnsDetails->Nameserver as $ns) {
                    $nameservers[] = (string) $ns;
                }
            }

            $status = (string) $domainInfo['Status'];
            $createdDate = (string) ($domainDetails->CreatedDate ?? '');
            $expiredDate = (string) ($domainDetails->ExpiredDate ?? '');

            return [
                'success' => true,
                'domain' => (string) $domainInfo['DomainName'],
                'status' => [$status],
                'registrant' => (string) ($domainInfo['OwnerName'] ?? ''),
                'created_date' => $createdDate,
                'expiry_date' => $expiredDate,
                'locked' => mb_strtolower((string) ($domainInfo->LockDetails['locked'] ?? 'false')) === 'true',
                'whoisguard_enabled' => mb_strtolower((string) ($domainInfo->Whoisguard['Enabled'] ?? 'false')) === 'true',
                'auto_renew' => false,
                'nameservers' => $nameservers,
            ];

        } catch (Exception $exception) {
            Log::error('Namecheap Domain Info API Error:', [
                'domain' => $domain,
                'message' => $exception->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $exception->getMessage(),
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

            throw_if(! property_exists($xml->CommandResponse, 'DomainRenewResult') || $xml->CommandResponse->DomainRenewResult === null, Exception::class, 'Invalid API response: Missing DomainRenewResult');

            $renewalResult = $xml->CommandResponse->DomainRenewResult;

            return [
                'success' => true,
                'domain' => $domain,
                'expiry_date' => (string) ($renewalResult['RenewedUntil'] ?? ''),
                'message' => 'Domain renewed successfully',
            ];

        } catch (Exception $exception) {
            return [
                'success' => false,
                'message' => 'Failed to renew domain: '.$exception->getMessage(),
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

        } catch (Exception $exception) {
            return [
                'success' => false,
                'message' => 'Failed to initiate domain transfer: '.$exception->getMessage(),
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

        } catch (Exception $exception) {
            return [
                'success' => false,
                'message' => 'Failed to get domain list: '.$exception->getMessage(),
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

            throw_if(! property_exists($xml->CommandResponse, 'DomainSetAutoRenewResult') || $xml->CommandResponse->DomainSetAutoRenewResult === null, Exception::class, 'Invalid API response: Missing DomainSetAutoRenewResult');

            $result = $xml->CommandResponse->DomainSetAutoRenewResult;
            $isSuccess = mb_strtolower((string) $result['IsSuccess']) === 'true';

            throw_unless($isSuccess, Exception::class, 'Failed to update auto-renewal status');

            return [
                'success' => true,
                'domain' => $domain,
                'auto_renew' => $autoRenew,
                'message' => $autoRenew ? 'Auto-renewal enabled' : 'Auto-renewal disabled',
            ];

        } catch (Exception $exception) {
            return [
                'success' => false,
                'message' => 'Failed to set auto-renewal: '.$exception->getMessage(),
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

            throw_if(! property_exists($xml->CommandResponse, 'UserGetBalancesResult') || $xml->CommandResponse->UserGetBalancesResult === null, Exception::class, 'Invalid API response: Missing UserGetBalancesResult');

            $result = $xml->CommandResponse->UserGetBalancesResult;

            return [
                'success' => true,
                'account_balance' => (float) ($result['AccountBalance'] ?? 0),
                'available_balance' => (float) ($result['AvailableBalance'] ?? 0),
                'currency' => 'USD',
            ];

        } catch (Exception $exception) {
            return [
                'success' => false,
                'message' => 'Failed to get user balance: '.$exception->getMessage(),
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

        } catch (Exception $exception) {
            return [
                'success' => false,
                'message' => 'Failed to validate domain for transfer: '.$exception->getMessage(),
            ];
        }
    }

    /**
     * Reactivate an expired domain
     */
    public function reActivateDomain(string $domain): array
    {
        try {
            $params = [
                'ApiUser' => $this->apiUser,
                'ApiKey' => $this->apiKey,
                'UserName' => $this->username,
                'ClientIp' => $this->clientIp,
                'Command' => 'namecheap.domains.reactivate',
                'DomainName' => $domain,
            ];

            $xml = $this->makeApiCall($params);

            throw_if(! property_exists($xml->CommandResponse, 'DomainReactivateResult') || $xml->CommandResponse->DomainReactivateResult === null, Exception::class, 'Invalid API response: Missing DomainReactivateResult');

            $result = $xml->CommandResponse->DomainReactivateResult;
            $isSuccess = mb_strtolower((string) $result['IsSuccess']) === 'true';

            throw_unless($isSuccess, Exception::class, 'Failed to reactivate domain');

            return [
                'success' => true,
                'domain' => (string) $result['Domain'],
                'charged_amount' => (float) ($result['ChargedAmount'] ?? 0),
                'order_id' => (string) ($result['OrderID'] ?? ''),
                'transaction_id' => (string) ($result['TransactionID'] ?? ''),
                'message' => 'Domain reactivated successfully',
            ];

        } catch (Exception $exception) {
            Log::error('Namecheap domain reactivation failed', [
                'domain' => $domain,
                'error' => $exception->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to reactivate domain: '.$exception->getMessage(),
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

        throw_unless($domainInfo['success'], Exception::class, 'Failed to get domain information');

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
            throw_unless(isset($contactInfo[$role]), Exception::class, 'Missing contact information for role: '.$role);

            $contact = $contactInfo[$role];
            foreach ($requiredFields as $field) {
                throw_if(empty($contact[$field]), Exception::class, sprintf("Missing required field '%s' for %s contact", $field, $role));
            }

            // Validate email format
            if (! filter_var($contact['email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception(sprintf('Invalid email format for %s contact: ', $role).$contact['email']);
            }

            // Validate country code
            if (mb_strlen((string) $contact['country_code']) !== 2) {
                throw new Exception(sprintf('Invalid country code for %s contact. Must be 2-letter ISO code: ', $role).$contact['country_code']);
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
        } catch (ConnectionException $connectionException) {
            Log::error('Namecheap API connection failed', [
                'error' => $connectionException->getMessage(),
                'url' => $this->apiBaseUrl,
            ]);
            throw new Exception('Failed to connect to Namecheap API: '.$connectionException->getMessage(), $connectionException->getCode(), $connectionException);
        }

        if (! $response->successful()) {
            Log::error('Namecheap API request failed', [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);
            throw new Exception('API request failed with HTTP status: '.$response->status());
        }

        $responseBody = $response->body();
        throw_if(empty($responseBody), Exception::class, 'Empty response from Namecheap API');

        // Log the full response for debugging
        Log::info('Namecheap API Response', [
            'response_body' => $responseBody,
        ]);

        try {
            $xml = new SimpleXMLElement($responseBody);
        } catch (Exception $exception) {
            Log::error('Failed to parse Namecheap API XML response', [
                'body' => $responseBody,
                'error' => $exception->getMessage(),
            ]);
            throw new Exception('Invalid XML response from Namecheap API: '.$exception->getMessage(), $exception->getCode(), $exception);
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
        throw_if($phone === '' || $phone === '0', Exception::class, 'Phone number is required');

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

        throw_if($phone === '' || $phone === '0', Exception::class, 'Invalid phone number format');

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
