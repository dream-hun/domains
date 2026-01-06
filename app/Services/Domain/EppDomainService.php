<?php

declare(strict_types=1);

namespace App\Services\Domain;

use AfriCC\EPP\Client as EPPClient;
use AfriCC\EPP\Frame\Command\Check\Contact as CheckContact;
use AfriCC\EPP\Frame\Command\Check\Domain as CheckDomain;
use AfriCC\EPP\Frame\Command\Check\Host as CheckHost;
use AfriCC\EPP\Frame\Command\Create\Contact as CreateContact;
use AfriCC\EPP\Frame\Command\Create\Domain as CreateDomain;
use AfriCC\EPP\Frame\Command\Create\Host as CreateHost;
use AfriCC\EPP\Frame\Command\Delete\Contact as DeleteContact;
use AfriCC\EPP\Frame\Command\Delete\Domain as DeleteDomain;
use AfriCC\EPP\Frame\Command\Info\Contact as InfoContact;
use AfriCC\EPP\Frame\Command\Info\Domain as InfoDomain;
use AfriCC\EPP\Frame\Command\Poll;
use AfriCC\EPP\Frame\Command\Renew\Domain as RenewDomain;
use AfriCC\EPP\Frame\Command\Transfer\Domain as TransferDomain;
use AfriCC\EPP\Frame\Command\Update\Domain as UpdateDomain;
use AfriCC\EPP\Frame\Response;
use App\Enums\DomainType;
use App\Models\Contact;
use App\Models\Domain;
use App\Models\DomainPrice;
use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;
use Throwable;

class EppDomainService implements DomainRegistrationServiceInterface
{
    private ?EPPClient $client = null;

    private array $config;

    private bool $connected = false;

    private int $maxRetries = 3;

    private int $retryDelay = 1; // seconds

    private array $commonTlds = [];

    // Domain suggestion patterns
    private array $suggestionPatterns = [
        'prefix' => ['my', 'get', 'best', 'top', 'pro', 'smart', 'quick', 'easy', 'fast'],
        'suffix' => ['hub', 'pro', 'plus', 'max', 'prime', 'elite', 'premium', 'gold', 'silver'],
        'separators' => ['', '-', '.', '_'],
    ];

    /**
     * @throws Exception|Throwable
     */
    public function __construct()
    {
        $this->config = config('services.epp');

        throw_if($this->config === [], Exception::class, 'EPP configuration not found');

        throw_if(empty($this->config['host']), Exception::class, 'EPP host is not configured. Please set EPP_HOST in your .env file.');

        throw_if(empty($this->config['certificate']) || ! file_exists($this->config['certificate']), Exception::class, 'EPP certificate not found. Please check the certificate path in your configuration.');

        $this->initializeClient();
    }

    /**
     * Destructor to ensure connection is closed
     */
    public function __destruct()
    {
        $this->disconnect();
    }

    /**
     * Check if a domain is available for registration
     *
     * @param  array  $domains  Array of domain names to check
     * @return array{available: bool, reason: string}
     */
    /**
     * @return array<string, array{available: bool, reason: string, is_premium?: bool, premium_price?: float|null, eap_fee?: float}>
     */
    public function checkAvailability(array $domains): array
    {
        try {
            $results = $this->checkDomain($domains);
            $formattedResults = [];

            foreach ($domains as $domain) {
                if (isset($results[$domain])) {
                    $formattedResults[$domain] = [
                        'available' => $results[$domain]->available ?? false,
                        'reason' => $results[$domain]->reason ?? 'Domain check completed',
                    ];
                } else {
                    $formattedResults[$domain] = [
                        'available' => false,
                        'reason' => 'Domain check failed',
                    ];
                }
            }

            return $formattedResults;
        } catch (Exception $exception) {
            Log::error('Domain availability check failed', [
                'domains' => $domains,
                'error' => $exception->getMessage(),
            ]);

            $formattedResults = [];
            foreach ($domains as $domain) {
                $formattedResults[$domain] = [
                    'available' => false,
                    'reason' => 'Service temporarily unavailable: '.$exception->getMessage(),
                ];
            }

            return $formattedResults;
        }
    }

    /**
     * Suggest other available domains to the user
     *
     * @param  string  $domain  Base domain name without TLD
     * @return array Array of suggested domains with availability and pricing
     */
    public function suggestDomains(string $domain): array
    {
        try {
            $baseName = $this->extractBaseName($domain);
            $suggestions = [];

            // Generate domain suggestions
            $suggestedDomains = $this->generateDomainSuggestions($baseName);

            // Check availability for all suggestions
            $availabilityResults = $this->searchDomains($baseName, $this->getCommonTlds());

            // Process results and add pricing information
            foreach ($suggestedDomains as $suggestedDomain) {
                $tld = $this->extractTld($suggestedDomain);
                $priceInfo = DomainPrice::query()->where('tld', $tld)->first();

                $suggestions[] = [
                    'domain' => $suggestedDomain,
                    'available' => $availabilityResults[$suggestedDomain]['available'] ?? false,
                    'price' => $priceInfo?->getFormattedPrice(),
                    'type' => $priceInfo?->type->value ?? 'unknown',
                    'reason' => $availabilityResults[$suggestedDomain]['reason'] ?? null,
                    'suggestion_type' => $this->getSuggestionType($suggestedDomain, $baseName),
                ];
            }

            // Filter to show only available domains first, then sort by price
            $available = array_filter($suggestions, fn (array $s) => $s['available']);
            $unavailable = array_filter($suggestions, fn (array $s): bool => ! $s['available']);

            // Sort available domains by price (lowest first)
            usort(
                $available,
                static fn (array $a, array $b): int => ($a['price'] ? (float) preg_replace('/[^0-9.]/', '', $a['price']) : PHP_FLOAT_MAX)
                    <=> ($b['price'] ? (float) preg_replace('/[^0-9.]/', '', $b['price']) : PHP_FLOAT_MAX)
            );

            return array_merge($available, $unavailable);

        } catch (Exception $exception) {
            Log::error('Domain suggestions failed', [
                'domain' => $domain,
                'error' => $exception->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Register a new domain
     *
     * @param  string  $domain  The domain name to register
     * @param  array<string, mixed>  $contactInfo  Contact information for the domain
     * @param  int  $years  Number of years to register the domain for
     * @return array{success: bool, domain?: string, message?: string}
     */
    public function registerDomain(string $domain, array $contactInfo, int $years): array
    {
        try {
            $this->ensureConnection();

            $registrantContactId = $contactInfo['registrant'] ?? null;
            $adminContactId = $contactInfo['admin'] ?? null;
            $technicalContactId = $contactInfo['technical'] ?? null;
            $billingContactId = $contactInfo['billing'] ?? null;

            throw_if(! $registrantContactId || ! $adminContactId || ! $technicalContactId || ! $billingContactId, Exception::class, 'All contact IDs are required for domain registration');

            // Create domain frame using existing contact IDs
            $period = $years.'y';
            $nameservers = $contactInfo['nameservers'] ?? ['ns1.example.com', 'ns2.example.com'];

            $frame = $this->createDomain(
                $domain,
                $period,
                $nameservers,
                $registrantContactId,
                $adminContactId,
                $technicalContactId,
                $billingContactId
            );

            // Send registration request
            $response = $this->client->request($frame);

            if (! $response || ! $response->success()) {
                $message = $response ? $response->message() : 'Unknown error';
                throw new Exception('Domain registration failed: '.$message);
            }

            return [
                'success' => true,
                'domain' => $domain,
                'message' => 'Domain registered successfully with EPP registry',
            ];

        } catch (Exception $exception) {
            Log::error('Domain registration failed', [
                'domain' => $domain,
                'error' => $exception->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Domain registration failed: '.$exception->getMessage(),
            ];
        }
    }

    /**
     * Renew a domain registration
     *
     * @param  string  $domain  The domain name to renew
     * @param  int  $years  Number of years to renew the domain for
     * @return array{success: bool, domain?: string, expiry_date?: string, message?: string}
     */
    public function renewDomainRegistration(string $domain, int $years): array
    {
        try {
            $this->ensureConnection();

            // Get current domain info
            $domainInfo = $this->getDomainInfo($domain);
            if (! $domainInfo['success']) {
                $message = $domainInfo['message'] ?? 'Unknown error';
                throw new Exception('Failed to get domain information: '.$message);
            }

            // Use expiry_date from domain info - this is the exact format expected by the registry
            $currentExpiry = $domainInfo['expiry_date'] ?? null;
            throw_unless($currentExpiry, Exception::class, 'Failed to get current expiry date from domain info');

            $period = $years.'y';

            // Create renewal frame
            $frame = $this->renewDomain($domain, $currentExpiry, $period);

            // Send renewal request
            $response = $this->client->request($frame);

            if (! $response || ! $response->success()) {
                $message = $response ? $response->message() : 'Unknown error';
                throw new Exception('Domain renewal failed: '.$message);
            }

            // Update domain record in database
            $domainModel = Domain::query()->where('name', $domain)->first();

            if ($domainModel) {
                $domainModel->update([
                    'expires_at' => $domainModel->expires_at->addYears($years),
                    'last_renewed_at' => now(),
                ]);
            }

            $newExpiry = ($domainModel !== null && $domainModel->expires_at !== null) ? $domainModel->expires_at : now()->addYears($years);

            return [
                'success' => true,
                'domain' => $domain,
                'expiry_date' => $newExpiry->format('Y-m-d'),
                'message' => 'Domain renewed successfully',
            ];

        } catch (Exception $exception) {
            Log::error('Domain renewal failed', [
                'domain' => $domain,
                'error' => $exception->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Domain renewal failed: '.$exception->getMessage(),
            ];
        }
    }

    /**
     * Transfer a domain from another registrar
     *
     * @param  string  $domain  The domain name to transfer
     * @param  string  $authCode  Authorization code for the transfer
     * @param  array<string, mixed>  $contactInfo  Contact information for the domain
     * @return array{success: bool, domain?: string, message?: string}
     */
    public function transferDomainRegistration(string $domain, string $authCode, array $contactInfo): array
    {
        try {
            $this->ensureConnection();

            // Check if domain is available for transfer (domain should NOT be available for registration)
            $availabilityCheck = $this->checkAvailability([$domain]);
            $domainResult = $availabilityCheck[$domain] ?? ['available' => false];
            throw_if($domainResult['available'], Exception::class, 'Domain is available for registration, not transfer');

            // Create contacts
            $registrantContact = $this->createContact($contactInfo['registrant'] ?? []);
            $adminContact = $this->createContact($contactInfo['admin'] ?? []);
            $billingContact = $this->createContact($contactInfo['billing'] ?? []);

            // Create transfer frame
            $frame = $this->transferDomain($domain, $authCode);

            // Send transfer request
            $response = $this->client->request($frame);

            if (! $response || ! $response->success()) {
                $message = $response ? $response->message() : 'Unknown error';
                throw new Exception('Domain transfer failed: '.$message);
            }

            // Create domain record in database
            $domainModel = Domain::query()->create([
                'name' => $domain,
                'owner_id' => $contactInfo['user_id'] ?? null,
                'registered_at' => now(),
                'expires_at' => now()->addYear(),
                'status' => 'transfer_pending',
            ]);

            // Attach contacts
            $domainModel->contacts()->attach([
                $registrantContact->id => ['type' => 'registrant'],
                $adminContact->id => ['type' => 'admin'],
                $billingContact->id => ['type' => 'billing'],
            ]);

            return [
                'success' => true,
                'domain' => $domain,
                'message' => 'Domain transfer initiated successfully',
            ];

        } catch (Exception $exception) {
            Log::error('Domain transfer failed', [
                'domain' => $domain,
                'error' => $exception->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Domain transfer failed: '.$exception->getMessage(),
            ];
        }
    }

    /**
     * Create a contact
     *
     * @param  array<string, mixed>  $contactData  Contact information
     * @return Contact The created contact
     *
     * @throws Exception
     */
    public function createContact(array $contactData): Contact
    {
        try {
            // Create contact in database first
            $contact = Contact::query()->create([
                'uuid' => Str::uuid(),
                'first_name' => $contactData['first_name'] ?? '',
                'last_name' => $contactData['last_name'] ?? '',
                'organization' => $contactData['organization'] ?? null,
                'email' => $contactData['email'] ?? '',
                'phone' => $contactData['phone'] ?? '',
                'address_one' => $contactData['address_one'] ?? '',
                'address_two' => $contactData['address_two'] ?? null,
                'city' => $contactData['city'] ?? '',
                'state_province' => $contactData['state_province'] ?? '',
                'postal_code' => $contactData['postal_code'] ?? '',
                'country_code' => $contactData['country_code'] ?? '',
                'contact_type' => $contactData['contact_type'] ?? 'registrant',
                'user_id' => $contactData['user_id'] ?? null,
            ]);

            // Create contact in EPP registry
            $eppContactData = [
                'contact_id' => $contact->id,
                'name' => $contact->full_name,
                'organization' => $contact->organization,
                'street1' => $contact->address_one,
                'street2' => $contact->address_two,
                'city' => $contact->city,
                'province' => $contact->state_province,
                'postal_code' => $contact->postal_code,
                'country_code' => $contact->country_code,
                'voice' => $contact->phone,
                'email' => $contact->email,
            ];

            $eppResult = $this->createContacts($eppContactData);

            throw_if($eppResult === [], Exception::class, 'Failed to create contact in EPP registry');

            return $contact;

        } catch (Exception $exception) {
            Log::error('Contact creation failed', [
                'contact_data' => $contactData,
                'error' => $exception->getMessage(),
            ]);
            throw $exception;
        }
    }

    /**
     * Get domain pricing information
     *
     * @param  string  $domain  The domain name to get pricing for
     * @return array{success: bool, price?: float, currency?: string, message?: string}
     */
    public function getDomainPricing(string $domain): array
    {
        try {
            $tld = $this->extractTld($domain);
            $priceInfo = DomainPrice::query()->where('tld', $tld)->first();

            if (! $priceInfo) {
                return [
                    'success' => false,
                    'message' => 'Pricing information not available for this TLD',
                ];
            }

            return [
                'success' => true,
                'price' => $priceInfo->getPriceInBaseCurrency('register_price'),
                'currency' => $priceInfo->getBaseCurrency(),
                'message' => 'Pricing information retrieved successfully',
            ];

        } catch (Exception $exception) {
            Log::error('Failed to get domain pricing', [
                'domain' => $domain,
                'error' => $exception->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to retrieve pricing information',
            ];
        }
    }

    /**
     * Get domain list for a user
     *
     * @param  int  $page  Page number
     * @param  int  $pageSize  Number of domains per page
     * @return array{success: bool, domains?: array, total?: int, message?: string}
     */
    public function getDomainList(int $page = 1, int $pageSize = 20): array
    {
        try {
            $query = Domain::with(['contacts', 'nameservers', 'domainPrice']);

            $total = $query->count();
            /** @var Collection<int, Domain> $domains */
            $domains = $query->skip(($page - 1) * $pageSize)
                ->take($pageSize)
                ->get();

            $mappedDomains = $domains->map(fn (Domain $domain): array => [
                'id' => $domain->id,
                'name' => $domain->name,
                'status' => $domain->status,
                'registered_at' => $domain->registered_at?->format('Y-m-d'),
                'expires_at' => $domain->expires_at?->format('Y-m-d'),
                'contacts' => $domain->contacts->map(fn (Contact $c): array => [
                    'id' => $c->id,
                    'name' => $c->full_name,
                    'type' => $c->pivot->type ?? 'unknown',
                ]),
                'nameservers' => $domain->nameservers->pluck('name'),
            ]);

            return [
                'success' => true,
                'domains' => $mappedDomains->all(),
                'total' => $total,
                'message' => 'Domain list retrieved successfully',
            ];

        } catch (Exception $exception) {
            Log::error('Failed to get domain list', [
                'page' => $page,
                'page_size' => $pageSize,
                'error' => $exception->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to retrieve domain list',
            ];
        }
    }

    /**
     * Update domain nameservers
     *
     * @param  string  $domain  The domain name
     * @param  array  $nameservers  Array of nameserver addresses
     * @return array{success: bool, message?: string}
     */
    public function updateNameservers(string $domain, array $nameservers): array
    {
        try {
            $this->ensureConnection();

            // Create update frame
            $frame = $this->updateDomainNameservers($domain, $nameservers);

            // Send update request
            $response = $this->client->request($frame);

            if (! $response || ! $response->success()) {
                $message = $response ? $response->message() : 'Unknown error';
                throw new Exception('Nameserver update failed: '.$message);
            }

            // Update was successful
            return [
                'success' => true,
                'message' => 'Nameservers updated successfully',
            ];
        } catch (Exception $exception) {
            Log::error('EPP nameserver update failed', [
                'domain' => $domain,
                'nameservers' => $nameservers,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Nameserver update failed: '.$exception->getMessage(),
            ];
        }
    }

    /**
     * Get domain nameservers
     *
     * @param  string  $domain  The domain name
     * @return array{success: bool, nameservers?: array, message?: string}
     */
    public function getNameservers(string $domain): array
    {
        try {
            $this->ensureConnection();

            // Get domain info from EPP
            $domainInfo = $this->getDomainInfo($domain);

            throw_unless($domainInfo['success'], Exception::class, 'Failed to get domain information');

            // Extract nameservers from the response XML if available
            // Since getDomainInfo returns status, registrant, created_date, expiry_date but not nameservers,
            // we'll need to get it from the database instead
            $domainModel = Domain::query()->where('name', $domain)->with('nameservers')->first();
            $nameservers = $domainModel ? $domainModel->nameservers->pluck('name')->toArray() : [];

            return [
                'success' => true,
                'nameservers' => $nameservers,
                'message' => 'Nameservers retrieved successfully',
            ];

        } catch (Exception $exception) {
            Log::error('Failed to get nameservers', [
                'domain' => $domain,
                'error' => $exception->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to retrieve nameservers: '.$exception->getMessage(),
            ];
        }
    }

    /**
     * Delete a contact from the EPP registry
     *
     * @param  string  $contactId  The ID of the contact to delete
     * @return array Response from EPP server
     *
     * @throws Exception
     */
    public function deleteContact(string $contactId): array
    {
        try {
            $this->connectWithRetry();

            $frame = new DeleteContact;
            $frame->setId($contactId);

            $response = $this->client->request($frame);

            // Handle response based on its type
            if (method_exists($response, 'getMessage')) {
                $message = $response->message();
            } elseif (method_exists($response, 'resultMessage')) {
                $message = $response->message();
            } else {
                $message = 'Operation completed';
            }

            return [
                'success' => $response->success(),
                'message' => $message,
                'code' => $response->code(),
            ];
        } catch (Exception $exception) {
            Log::error('EPP delete contact failed', [
                'contact_id' => $contactId,
                'error' => $exception->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
            ];
        }
    }

    /**
     * Connect to the EPP server with retries
     *
     * @throws Exception
     */
    public function connectWithRetry(): ?string
    {
        if ($this->connected) {
            return null;
        }

        $attempts = 0;
        $lastException = null;

        while ($attempts < $this->maxRetries) {
            try {
                $greeting = $this->client->connect();
                $this->connected = true;

                return $greeting;
            } catch (Exception $e) {
                $lastException = $e;
                $attempts++;
                Log::warning(sprintf('EPP Connection attempt %d failed: ', $attempts).$e->getMessage());

                if ($attempts < $this->maxRetries) {
                    Sleep::sleep($this->retryDelay);
                }
            }
        }

        Log::error('EPP Connection failed after '.$this->maxRetries.' attempts');
        throw $lastException;
    }

    /**
     * Connect to the EPP server (alias for connectWithRetry)
     *
     * @throws Exception
     */
    public function connect(): ?string
    {
        return $this->connectWithRetry();
    }

    /**
     * Get contact information from EPP registry
     *
     * @throws Exception
     */
    public function infoContact(string $contactId): ?array
    {
        try {
            $this->connectWithRetry();

            $frame = new InfoContact;
            $frame->setId($contactId);

            Log::info('Sending contact info request to EPP', [
                'contact_id' => $contactId,
            ]);

            $response = $this->client->request($frame);

            throw_unless($response, Exception::class, 'No response received from EPP server');

            $results = $response->results();
            throw_if(empty($results), Exception::class, 'Empty response from EPP server');

            $result = $results[0];
            if ($result->code() !== 1000) {
                Log::error('Failed to get contact info from EPP', [
                    'contact_id' => $contactId,
                    'code' => $result->code(),
                    'message' => $result->message(),
                ]);

                return null;
            }

            $data = $response->data();
            $contactData = $data['infData'] ?? null;
            if (! $contactData) {
                Log::error('Invalid contact info response from EPP', [
                    'contact_id' => $contactId,
                    'data' => $data,
                ]);

                return null;
            }

            // Format contact data
            return [
                'contact' => [
                    'id' => $contactData['id'] ?? '',
                    'name' => $contactData['postalInfo']['name'] ?? '',
                    'organization' => $contactData['postalInfo']['org'] ?? '',
                    'streets' => $contactData['postalInfo']['addr']['street'] ?? [],
                    'city' => $contactData['postalInfo']['addr']['city'] ?? '',
                    'province' => $contactData['postalInfo']['addr']['sp'] ?? '',
                    'postal_code' => $contactData['postalInfo']['addr']['pc'] ?? '',
                    'country_code' => $contactData['postalInfo']['addr']['cc'] ?? '',
                    'voice' => $contactData['voice'] ?? '',
                    'fax' => [
                        'number' => $contactData['fax'] ?? '',
                        'ext' => $contactData['faxExt'] ?? '',
                    ],
                    'email' => $contactData['email'] ?? '',
                    'status' => $contactData['status'] ?? [],
                    'auth_info' => $contactData['authInfo']['pw'] ?? '',
                ],
            ];
        } catch (Exception $exception) {
            Log::error('Exception while getting contact info from EPP', [
                'contact_id' => $contactId,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);
            throw $exception;
        }
    }

    /**
     * Check Domain Availability
     *
     * @throws Exception
     */
    public function checkDomain(array $domains): array
    {
        try {
            $this->ensureConnection();

            $frame = new CheckDomain;
            foreach ($domains as $domain) {
                $frame->addDomain($domain);
            }

            $response = $this->client->request($frame);
            throw_unless($response, Exception::class, 'No response received from EPP server');

            $results = [];
            $data = $response->data();

            Log::debug('EPP Response Data:', ['data' => $data]);

            if (! empty($data) && isset($data['chkData']['cd'])) {
                // Handle both single and multiple domain responses
                $items = isset($data['chkData']['cd'][0]) ? $data['chkData']['cd'] : [$data['chkData']['cd']];

                foreach ($items as $item) {
                    // Use improved extraction methods for consistency
                    $domainName = $this->extractDomainValue($item, 'name');
                    $available = $this->extractAvailabilityValue($item);
                    $reason = $this->extractReasonValue($item);

                    Log::debug('Processing domain result:', [
                        'domainName' => $domainName,
                        'available' => $available,
                        'item' => $item,
                    ]);

                    $results[$domainName] = (object) [
                        'available' => $available,
                        'reason' => $reason,
                    ];
                }
            } else {
                Log::warning('Unexpected response structure:', ['data' => $data]);
            }

            return $results;
        } catch (Exception $exception) {
            Log::error('Domain check error: '.$exception->getMessage());
            // Try to reconnect on next request
            $this->connected = false;
            throw $exception;
        }
    }

    /**
     * Check Contact availability
     *
     * @throws Exception
     */
    public function checkContacts(array $contactIds): array
    {
        try {
            $this->ensureConnection();
            $frame = new CheckContact;

            foreach ($contactIds as $contactId) {
                $frame->addId($contactId);
            }

            $response = $this->client->request($frame);
            throw_unless($response, Exception::class, 'No response received from EPP server');

            $results = [];
            $data = $response->data();

            Log::debug('EPP Contact Check Response:', ['data' => $data]);

            if (! empty($data) && isset($data['chkData'])) {
                // Handle both single and multiple contact responses
                $items = $data['chkData']['cd'] ?? [];
                if (! is_array($items) || ! isset($items[0])) {
                    $items = [$items];
                }

                foreach ($items as $item) {
                    // Extract contact ID and availability
                    $contactId = null;
                    $available = false;

                    if (isset($item['id'])) {
                        if (is_array($item['id']) && isset($item['id']['_text'])) {
                            $contactId = $item['id']['_text'];
                            $available = ($item['id']['@attributes']['avail'] ?? '') === '1';
                        } else {
                            $contactId = $item['id'];
                            $available = true; // If no explicit availability, assume available
                        }
                    }

                    if ($contactId) {
                        $results[$contactId] = (object) [
                            'available' => $available,
                            'reason' => $item['reason'] ?? null,
                        ];
                    }
                }
            }

            return $results;
        } catch (Exception $exception) {
            Log::error('Contact check failed: '.$exception->getMessage());
            // Try to reconnect on next request
            $this->connected = false;
            throw $exception;
        }
    }

    /**
     * Create Domain Contact
     *
     * @throws Exception
     */
    public function createContacts(array $contacts): array
    {
        try {
            $this->ensureConnection();

            Log::debug('Creating contact with data:', ['contacts' => $contacts]);

            // Validate required contact data
            $requiredFields = ['contact_id', 'name', 'street1', 'city', 'country_code', 'voice', 'email'];
            foreach ($requiredFields as $field) {
                throw_if(empty($contacts[$field]), Exception::class, sprintf('Invalid contact data: %s is required', $field));

                throw_unless(is_string($contacts[$field]), Exception::class, sprintf('Invalid contact data: %s must be a string', $field));
            }

            $frame = new CreateContact;
            $frame->setId($contacts['contact_id']);
            $frame->setName($contacts['name']);

            if (! empty($contacts['organization'])) {
                throw_unless(is_string($contacts['organization']), Exception::class, 'Invalid contact data: organization must be a string');

                $frame->setOrganization($contacts['organization']);
            }

            // Handle street addresses
            $frame->addStreet($contacts['street1']);

            if (! empty($contacts['street2'])) {
                throw_unless(is_string($contacts['street2']), Exception::class, 'Invalid contact data: street2 must be a string');

                $frame->addStreet($contacts['street2']);
            }

            // Validate and set required city
            throw_if(empty($contacts['city']), Exception::class, 'Invalid contact data: city is required');

            $frame->setCity($contacts['city']);

            // Optional province
            if (! empty($contacts['province'])) {
                $frame->setProvince($contacts['province']);
            }

            // Optional postal code
            if (! empty($contacts['postal_code'])) {
                $frame->setPostalCode($contacts['postal_code']);
            }

            // Validate and set required country code
            throw_if(empty($contacts['country_code']), Exception::class, 'Invalid contact data: country_code is required');

            $frame->setCountryCode($contacts['country_code']);

            // Format phone number to EPP format (+CC.number)
            throw_if(empty($contacts['voice']), Exception::class, 'Invalid contact data: voice (phone) is required');

            $phone = $contacts['voice'];
            throw_unless(is_string($phone), Exception::class, 'Invalid contact data: voice (phone) must be a string');

            if (! str_starts_with($phone, '+')) {
                // Add country code for Rwanda if not present

                $phone = '+250.'.mb_ltrim($phone, '0');

                $phone = '+250.'.mb_ltrim($phone, '0');
            }

            $frame->setVoice($phone);

            // Handle optional fax
            if (! empty($contacts['fax'])) {
                $fax = $contacts['fax'];
                throw_unless(is_string($fax), Exception::class, 'Invalid contact data: fax must be a string');

                if (! str_starts_with($fax, '+')) {

                    $fax = '+250.'.mb_ltrim($fax, '0');

                    $fax = '+250.'.mb_ltrim($fax, '0');
                }

                $frame->setFax($fax, $contacts['fax_ext'] ?? '');
            }

            // Validate and set required email
            throw_if(empty($contacts['email']), Exception::class, 'Invalid contact data: email is required');

            throw_unless(is_string($contacts['email']), Exception::class, 'Invalid contact data: email must be a string');

            $frame->setEmail($contacts['email']);

            $auth = $frame->setAuthInfo();

            // Send the frame and get response
            $response = $this->client->request($frame);

            throw_unless($response, Exception::class, 'No response received from EPP server');

            $results = $response->results();
            throw_if(empty($results), Exception::class, 'Empty response from EPP server');

            $result = $results[0];
            if ($result->code() !== 1000) {
                Log::error('Failed to create contact in EPP', [
                    'code' => $result->code(),
                    'message' => $result->message(),
                ]);
                throw new Exception('Failed to create contact in EPP registry: '.$result->message());
            }

            Log::info('Contact created successfully in EPP', [
                'contact_id' => $contacts['contact_id'],
                'code' => $result->code(),
                'message' => $result->message(),
            ]);

            return [
                'contact_id' => $contacts['contact_id'],
                'auth' => $auth,
                'code' => $result->code(),
                'message' => $result->message(),
            ];
        } catch (Exception $exception) {
            Log::error('Contact creation failed: '.$exception->getMessage());
            // Try to reconnect on next request
            $this->connected = false;
            throw $exception;
        }
    }

    /**
     * Update Domain Contact in EPP registry
     *
     * @param  string  $contactId  The ID of the contact to update
     * @param  array  $contactData  Contact data to update
     * @return array Response from EPP server
     *
     * @throws Exception
     */
    public function updateContact(string $contactId, array $contactData): array
    {
        try {
            $this->ensureConnection();

            // Note: Contact updates in EPP are complex and the php-epp2 library
            // may not support all update operations directly.
            // The safest approach is to delete and recreate the contact if updates are needed.
            // For now, we'll log the limitation and return an error.

            Log::warning('Contact update attempted but not fully supported by EPP library', [
                'contact_id' => $contactId,
                'contact_data' => $contactData,
            ]);

            return [
                'success' => false,
                'message' => 'Contact updates are not fully supported via EPP protocol. Please delete and recreate the contact if changes are needed.',
                'code' => 0,
                'auth' => null,
            ];
        } catch (Exception $exception) {
            Log::error('Contact update failed: '.$exception->getMessage(), [
                'contact_id' => $contactId,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);
            // Try to reconnect on the next request
            $this->connected = false;
            throw $exception;
        }
    }

    /**
     * Check available hosts
     *
     * @throws Exception
     */
    public function checkHosts(array $hosts): CheckHost
    {
        try {
            $this->ensureConnection();
            $frame = new CheckHost;

            foreach ($hosts as $host) {
                $frame->addHost($host);
            }

            return $frame;
        } catch (Exception $exception) {
            Log::error('Host check failed: '.$exception->getMessage());
            // Try to reconnect on next request
            $this->connected = false;
            throw $exception;
        }
    }

    /**
     * Create Hosts
     *
     * @throws Exception
     */
    public function createHost(string $host, array $addresses): CreateHost
    {
        try {
            $this->ensureConnection();
            $frame = new CreateHost;
            $frame->setHost($host);

            foreach ($addresses as $address) {
                $frame->addAddr($address);
            }

            return $frame;
        } catch (Exception $exception) {
            Log::error('Host creation failed: '.$exception->getMessage());
            // Try to reconnect on next request
            $this->connected = false;
            throw $exception;
        }
    }

    /**
     * Create domain functionality
     *
     * @throws Exception
     */
    public function createDomain(string $domain, string $period, array $nameservers, string $registrant, string $adminContact, string $techContact, string $billingContact): CreateDomain
    {
        try {
            $this->ensureConnection();
            $frame = new CreateDomain;
            $frame->setDomain($domain);
            $frame->setPeriod($period);

            // Use hostObj instead of hostAttr as required by the EPP server
            foreach ($nameservers as $host) {
                if (! empty($host)) {
                    // Make sure the host is properly formatted
                    // Ensure $host is a string before passing to mb_trim
                    $host = (string) $host;
                    $host = mb_trim($host);
                    if ($host !== '' && $host !== '0') {
                        // Log the nameserver being added
                        Log::info('Adding nameserver to domain', [
                            'domain' => $domain,
                            'nameserver' => $host,
                        ]);
                        $frame->addHostObj($host);
                    }
                }
            }

            $frame->setRegistrant($registrant);
            $frame->setAdminContact($adminContact);
            $frame->setTechContact($techContact);
            $frame->setBillingContact($billingContact);

            $frame->setAuthInfo();

            return $frame;
        } catch (Exception $exception) {
            Log::error('Domain creation failed: '.$exception->getMessage());
            // Try to reconnect on next request
            $this->connected = false;
            throw $exception;
        }
    }

    /**
     * Get Auth Code to use in Domain transfer
     *
     * @throws Exception
     */
    public function getAuthorizationCode(string $domain): string
    {
        try {
            $this->ensureConnection();

            $frame = new InfoDomain();
            $frame->setDomain($domain);

            $response = $this->client->request($frame);

            throw new Exception('EPP InfoDomain command failed: '.$response->message());
        } catch (Exception $exception) {
            Log::error('EPP getAuthorizationCode failed: '.$exception->getMessage());
            throw $exception;
        }
    }

    /**
     * Renew Domain
     *
     * @throws Exception
     */
    public function renewDomain(string $domain, $currentExpirationDate, string $period): RenewDomain
    {
        try {
            $this->ensureConnection();

            // CRITICAL: For EPP renewal, we MUST use EXACTLY the date format provided by the registry
            // The registry expects the exact same string it provided, including full ISO format with timezone
            // Example: "2027-02-28T06:32:27.850Z" - NOT just "2027-02-28"

            // Only format if it's a DateTime object (should be avoided for renewals)
            if ($currentExpirationDate instanceof DateTimeImmutable) {
                // Use ISO 8601 format with timezone to match registry format
                $currentExpirationDate = $currentExpirationDate->format(DateTimeInterface::class);
                Log::warning('Converting DateTime to string for EPP renewal - this may cause issues', [
                    'domain' => $domain,
                    'formatted_date' => $currentExpirationDate,
                ]);
            } elseif (! is_string($currentExpirationDate)) {
                // If we received something unexpected (not string or DateTime), use current date
                Log::error('Unexpected date type for renewal, using ISO format', [
                    'domain' => $domain,
                    'date_type' => gettype($currentExpirationDate),
                ]);
                $currentExpirationDate = (new DateTimeImmutable)->format(DateTimeInterface::class);
            } else {
                // It's already a string - log but don't modify at all
                Log::info('Using exact registry date string for EPP renewal', [
                    'domain' => $domain,
                    'raw_date' => $currentExpirationDate,
                ]);
            }

            $frame = new RenewDomain;
            $frame->setDomain($domain);
            $frame->setCurrentExpirationDate($currentExpirationDate);
            $frame->setPeriod($period);

            // Log the renewal attempt with configuration context
            Log::info('Attempting domain renewal', [
                'domain' => $domain,
                'expiration_date' => $currentExpirationDate,
                'period' => $period,
                'epp_host' => $this->config['host'],
            ]);

            return $frame;
        } catch (Exception $exception) {
            Log::error('Domain renewal failed: '.$exception->getMessage(), [
                'domain' => $domain,
                'expiration_date' => $currentExpirationDate,
                'period' => $period,
                'epp_host' => $this->config['host'],
                'trace' => $exception->getTraceAsString(),
            ]);
            // Try to reconnect on next request
            $this->connected = false;
            throw $exception;
        }
    }

    /**
     * Check if a domain is available or registered
     *
     * @return array{available: bool, reason?: string}
     *
     * @throws Exception
     */
    public function checkSingleDomain(string $domain): array
    {
        try {
            $this->ensureConnection();

            Log::info('Checking domain availability', [
                'domain' => $domain,
                'epp_host' => $this->config['host'],
            ]);

            $frame = new CheckDomain();
            $frame->addDomain($domain);

            $client = $this->client;
            $response = $client->request($frame);

            throw_unless($response instanceof Response, Exception::class, 'Invalid response from registry');

            $result = $response->results()[0];
            $responseData = $response->data();

            throw_if(! is_array($responseData) || ! isset($responseData['chkData']['cd']), Exception::class, 'Unexpected response data format');

            $checkData = $responseData['chkData']['cd'][0];
            $available = (bool) ($checkData['avail'] ?? false);
            $reason = $checkData['reason'] ?? null;

            Log::debug('Domain check result', [
                'domain' => $domain,
                'available' => $available,
                'reason' => $reason,
            ]);

            return [
                'available' => $available,
                'reason' => $reason,
            ];
        } catch (Exception $exception) {
            Log::error('Domain check failed: '.$exception->getMessage(), [
                'domain' => $domain,
                'epp_host' => $this->config['host'],
                'trace' => $exception->getTraceAsString(),
            ]);
            $this->connected = false;
            throw $exception;
        }
    }

    /**
     * Delete Domain
     *
     * @throws Exception
     */
    public function deleteDomain(string $domain): DeleteDomain
    {
        try {
            $this->ensureConnection();
            $frame = new DeleteDomain;
            $frame->setDomain($domain);

            return $frame;
        } catch (Exception $exception) {
            Log::error('Domain deletion failed: '.$exception->getMessage());
            // Try to reconnect on next request
            $this->connected = false;
            throw $exception;
        }
    }

    /**
     * Acknowledge domain messages
     *
     * @throws Exception
     */
    public function pollAcknowledge(string $messageId): Poll
    {
        try {
            $this->ensureConnection();
            $frame = new Poll;
            $frame->ack($messageId);

            return $frame;
        } catch (Exception $exception) {
            Log::error('Poll acknowledge failed: '.$exception->getMessage());
            // Try to reconnect on next request
            $this->connected = false;
            throw $exception;
        }
    }

    /**
     * Update domain nameservers
     *
     * @param  string  $domain  Domain name
     * @param  array  $nameservers  Array of nameserver hostnames
     * @return UpdateDomain EPP frame
     *
     * @throws Exception
     */
    public function updateDomainNameservers(string $domain, array $nameservers): UpdateDomain
    {
        try {
            $this->ensureConnection();

            // Normalize domain name (remove trailing dot if present)
            $domain = mb_rtrim($domain, '.');

            // Filter out empty nameservers and normalize hostnames
            $nameservers = array_filter(array_map(function ($ns): string {
                // Normalize nameserver hostname (remove trailing dot if present)
                // Ensure $ns is a string before passing to mb_trim
                $ns = is_null($ns) ? '' : (string) $ns;

                return mb_rtrim(mb_trim($ns), '.');
            }, $nameservers), fn (string $ns): bool => $ns !== '' && $ns !== '0');

            // Get current nameservers for the domain
            $infoFrame = new InfoDomain;
            $infoFrame->setDomain($domain);
            $infoResponse = $this->client->request($infoFrame);

            // Create update domain frame
            $frame = new UpdateDomain;
            $frame->setDomain($domain);

            foreach ($nameservers as $ns) {

                $checkFrame = new CheckHost;
                $checkFrame->addHost($ns);
                $checkResponse = $this->client->request($checkFrame);

                if ($checkResponse->code() === 1000) {

                    $responseXml = (string) $checkResponse;
                    if (mb_strpos($responseXml, '<host:name avail="1">') !== false && mb_strpos($ns, $domain) !== false) {
                        Log::info('Creating subordinate host: '.$ns);

                        $createFrame = new CreateHost;
                        $createFrame->setHost($ns);
                        $createFrame->addAddr('127.0.0.1');
                        $createResponse = $this->client->request($createFrame);
                        if ($createResponse->code() !== 1000) {
                            Log::warning(sprintf('Failed to create host %s: %s', $ns, $createResponse->message()));
                        } else {
                            Log::info('Successfully created host '.$ns);
                        }
                    }
                }
            }

            if ($infoResponse->code() === 1000) {
                $responseXml = (string) $infoResponse;

                preg_match_all('/<domain:hostObj>([^<]+)<\/domain:hostObj>/', $responseXml, $matches);

                foreach ($matches[1] as $currentNs) {
                    Log::info('Removing nameserver: '.$currentNs);
                    $frame->removeHostObj($currentNs);
                }
            }

            foreach ($nameservers as $ns) {
                Log::info('Adding nameserver: '.$ns);
                $frame->addHostObj($ns);
            }

            $authInfo = Str::random(12);
            $frame->changeAuthInfo($authInfo);

            Log::debug('EPP update domain nameservers frame created', [
                'domain' => $domain,
                'new_nameservers' => $nameservers,
                'frame' => (string) $frame,
            ]);

            return $frame;
        } catch (Exception $exception) {
            Log::error('EPP update domain nameservers error', [
                'domain' => $domain,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);
            throw $exception;
        }
    }

    /**
     * Update domain contacts
     *
     * @param  string  $domain  Domain name
     * @param  array  $contactInfo  Array of contacts by type (registrant, admin, tech, billing)
     * @return array{success: bool, message?: string}
     *
     * @throws Exception
     */
    public function updateDomainContacts(string $domain, array $contactInfo): array
    {
        try {
            $this->ensureConnection();
            $domain = mb_rtrim($domain, '.');
            $frame = new UpdateDomain;
            $frame->setDomain($domain);

            // Get current domain info to identify existing contacts
            $infoFrame = new InfoDomain;
            $infoFrame->setDomain($domain);
            $infoResponse = $this->client->request($infoFrame);
            $currentContacts = [];

            if ($infoResponse->code() === 1000) {
                $responseXml = (string) $infoResponse;
                preg_match_all('/<domain:contact type="([^"]+)">([^<]+)<\/domain:contact>/', $responseXml, $matches, PREG_SET_ORDER);
                foreach ($matches as $match) {
                    $contactType = mb_strtolower($match[1]);
                    $contactId = $match[2];
                    $currentContacts[$contactType] = $contactId;
                }

                preg_match('/<domain:registrant>([^<]+)<\/domain:registrant>/', $responseXml, $registrantMatch);
                if (isset($registrantMatch[1]) && $registrantMatch[1] !== '0') {
                    $currentContacts['registrant'] = $registrantMatch[1];
                }
            }

            // Update contacts (registrant, admin, tech, billing)
            foreach (['registrant', 'admin', 'tech', 'billing'] as $type) {
                if (isset($contactInfo[$type]) && $contactInfo[$type] !== ($currentContacts[$type] ?? null)) {
                    if ($type === 'registrant') {
                        $frame->changeRegistrant($contactInfo[$type]);
                    } elseif ($type === 'admin') {
                        if (isset($currentContacts[$type])) {
                            $frame->removeAdminContact($currentContacts[$type]);
                        }

                        $frame->addAdminContact($contactInfo[$type]);
                    } elseif ($type === 'tech') {
                        if (isset($currentContacts[$type])) {
                            $frame->removeTechContact($currentContacts[$type]);
                        }

                        $frame->addTechContact($contactInfo[$type]);
                    } elseif ($type === 'billing') {
                        if (isset($currentContacts[$type])) {
                            $frame->removeBillingContact($currentContacts[$type]);
                        }

                        $frame->addBillingContact($contactInfo[$type]);
                    }
                }
            }

            // Send update request
            $response = $this->client->request($frame);
            if ($response && $response->code() === 1000) {
                return ['success' => true, 'message' => 'Domain contacts updated successfully.'];
            }

            return ['success' => false, 'message' => $response ? $response->message() : 'Unknown error updating contacts.'];
        } catch (Exception $exception) {
            Log::error('Domain contact update failed', [
                'domain' => $domain,
                'error' => $exception->getMessage(),
            ]);

            return ['success' => false, 'message' => 'Service error: '.$exception->getMessage()];
        }
    }

    /**
     * Transfer Domain
     *
     * @throws Exception
     */
    public function transferDomain(string $domain, string $authInfo, string $period = '1y'): TransferDomain
    {
        try {
            $this->ensureConnection();

            Log::info('Attempting domain transfer', [
                'domain' => $domain,
                'period' => $period,
                'epp_host' => $this->config['host'],
            ]);

            $frame = new TransferDomain();
            $frame->setDomain($domain);
            $frame->setAuthInfo($authInfo);
            $frame->setPeriod($period);

            return $frame;
        } catch (Exception $exception) {
            Log::error('Domain transfer failed: '.$exception->getMessage(), [
                'domain' => $domain,
                'period' => $period,
                'epp_host' => $this->config['host'],
                'trace' => $exception->getTraceAsString(),
            ]);
            $this->connected = false;
            throw $exception;
        }
    }

    /**
     * Get the EPP client instance
     */
    public function getClient(): EPPClient
    {
        return $this->client;
    }

    /**
     * Close the EPP connection
     */
    public function disconnect(): void
    {
        if ($this->client instanceof EPPClient) {
            $this->client->close();
            $this->connected = false;
            $this->client = null;
        }
    }

    public function checkNameserver(string $hostname): bool
    {
        $frame = new CheckHost();
        $frame->addHost($hostname);

        $response = $this->client->request($frame);

        return $response->code() === 1000;
    }

    /**
     * Search for domain availability with efficient batch processing
     *
     * @param  string  $searchTerm  Base domain name without TLD
     * @param  array|null  $specificTlds  Optional array of specific TLDs to check
     * @param  DomainType|null  $domainType  Optional domain type filter for suggestions
     * @return array Array of domain availability results
     *
     * @throws Exception
     */
    public function searchDomains(string $searchTerm, ?array $specificTlds = null, ?DomainType $domainType = null): array
    {
        try {
            $this->ensureConnection();

            // Get all active TLDs from DomainPrice if no specific TLDs provided
            if ($specificTlds === null) {
                $query = DomainPrice::query()->where('status', 'active');

                // Filter by domain type if specified
                if ($domainType instanceof DomainType) {
                    $query->where('type', $domainType);
                }

                $tlds = $query->latest()
                    ->limit(20)
                    ->pluck('tld')
                    ->toArray();
            } else {
                $tlds = $specificTlds;
            }

            $results = [];
            $batchSize = 5; // Process in small batches for better reliability
            $domainsToCheck = [];

            // Prepare all domain combinations
            foreach ($tlds as $tld) {
                $tld = $this->cleanTld($tld); // Remove leading dot if present
                if ($tld !== '' && $tld !== '0') {
                    $domainsToCheck[] = $searchTerm.'.'.$tld;
                }
            }

            // Process domains in batches
            foreach (array_chunk($domainsToCheck, $batchSize) as $batch) {
                $attempt = 0;
                $maxAttempts = 2;
                $success = false;

                while (! $success && $attempt < $maxAttempts) {
                    try {
                        $frame = new CheckDomain();
                        foreach ($batch as $domain) {
                            $frame->addDomain($domain);
                        }

                        $response = $this->client->request($frame);
                        $data = $response->data();

                        if (! empty($data) && isset($data['chkData']['cd'])) {
                            $items = isset($data['chkData']['cd'][0])
                                ? $data['chkData']['cd']
                                : [$data['chkData']['cd']];

                            foreach ($items as $item) {
                                $domainName = $this->extractDomainValue($item, 'name');
                                $available = $this->extractAvailabilityValue($item);
                                $reason = $this->extractReasonValue($item);

                                // Get pricing information
                                $tld = $this->extractTld($domainName);
                                $priceInfo = DomainPrice::query()->where('tld', '.'.$tld)
                                    ->where('status', 'active')
                                    ->first();

                                $results[$domainName] = [
                                    'domain' => $domainName,
                                    'available' => $available,
                                    'reason' => $reason,
                                    'price' => $priceInfo?->getFormattedPrice(),
                                    'type' => $priceInfo?->type->value ?? 'unknown',
                                ];
                            }
                        }

                        $success = true;
                    } catch (Exception $e) {
                        $attempt++;
                        Log::warning('Domain batch check failed', [
                            'attempt' => $attempt,
                            'domains' => $batch,
                            'error' => $e->getMessage(),
                        ]);

                        if ($attempt < $maxAttempts) {
                            Sleep::sleep(1);
                            $this->disconnect();
                            $this->ensureConnection();
                        } else {
                            // Mark domains as error if all attempts fail
                            foreach ($batch as $domain) {
                                $results[$domain] = [
                                    'domain' => $domain,
                                    'available' => false,
                                    'reason' => 'Service temporarily unavailable',
                                    'error' => true,
                                ];
                            }
                        }
                    }
                }
            }

            // Sort results by availability and price
            uasort($results, function (array $a, array $b): int {
                if ($a['available'] !== $b['available']) {
                    return $b['available'] <=> $a['available']; // Available domains first
                }

                // Extract numeric values from price strings for comparison
                $priceA = $a['price'] ? (float) preg_replace('/[^0-9.]/', '', (string) $a['price']) : PHP_FLOAT_MAX;
                $priceB = $b['price'] ? (float) preg_replace('/[^0-9.]/', '', (string) $b['price']) : PHP_FLOAT_MAX;

                return $priceA <=> $priceB; // Lower prices first
            });

            return $results;
        } catch (Exception $exception) {
            Log::error('Domain search failed', [
                'search_term' => $searchTerm,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);
            throw $exception;
        }
    }

    /**
     * Set domain lock status
     *
     * @param  string  $domain  The domain name
     * @param  bool  $lock  True to lock, false to unlock
     * @return array{success: bool, message?: string}
     */
    public function setDomainLock(string $domain, bool $lock): array
    {
        try {
            $this->ensureConnection();

            $frame = new UpdateDomain;
            $frame->setDomain($domain);

            // Add or remove clientTransferProhibited status
            if ($lock) {
                $frame->addStatus('clientTransferProhibited', 'Locked by user');
            } else {
                $frame->removeStatus('clientTransferProhibited');
            }

            $response = $this->client->request($frame);

            if ($response->code() !== 1000) {
                Log::error('EPP domain lock update failed', [
                    'domain' => $domain,
                    'lock' => $lock,
                    'response_code' => $response->code(),
                    'response_message' => $response->message(),
                ]);

                return [
                    'success' => false,
                    'message' => 'Failed to update domain lock: '.$response->message(),
                ];
            }

            Log::info('EPP domain lock updated successfully', [
                'domain' => $domain,
                'lock' => $lock,
            ]);

            return [
                'success' => true,
                'message' => $lock ? 'Domain locked successfully' : 'Domain unlocked successfully',
            ];

        } catch (Exception $exception) {
            Log::error('EPP domain lock update error', [
                'domain' => $domain,
                'lock' => $lock,
                'error' => $exception->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to update domain lock: '.$exception->getMessage(),
            ];
        }
    }

    /**
     * Reactivate an expired domain
     */
    public function reActivateDomain(string $domain): array
    {
        try {
            $this->ensureConnection();

            // For EPP, domain reactivation is typically done through renewal
            // since EPP doesn't have a specific reactivation command
            Log::info('Reactivating local domain via EPP renewal', [
                'domain' => $domain,
            ]);

            // Try to renew the domain for 1 year to reactivate it
            $result = $this->renewDomainRegistration($domain, 1);

            if ($result['success']) {
                return [
                    'success' => true,
                    'domain' => $domain,
                    'message' => 'Domain reactivated successfully via renewal',
                ];
            }

            return $result;

        } catch (Exception $exception) {
            Log::error('EPP domain reactivation failed', [
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
     * Set domain auto-renewal status
     */
    public function setAutoRenew(string $domain, bool $autoRenew): array
    {
        // EPP doesn't have auto-renewal functionality at the protocol level
        // This would need to be handled at the application level
        Log::info('Auto-renewal not supported via EPP protocol', [
            'domain' => $domain,
            'auto_renew' => $autoRenew,
        ]);

        return [
            'success' => false,
            'message' => 'Auto-renewal is not supported for local domains via EPP protocol',
        ];
    }

    /**
     * Get information about a domain
     *
     * @param  string  $domain  The domain name to get information for
     * @return array{success: bool, domain?: string, status?: array<string>, registrant?: string, created_date?: string, expiry_date?: string, message?: string}
     */
    public function getDomainInfo(string $domain): array
    {
        try {
            $this->ensureConnection();
            $domain = mb_rtrim($domain, '.');
            $frame = new InfoDomain();
            $frame->setDomain($domain);
            $response = $this->client->request($frame);

            if (! $response || $response->code() !== 1000) {
                return [
                    'success' => false,
                    'message' => $response ? $response->message() : 'Failed to retrieve domain info',
                ];
            }

            $responseXml = (string) $response;
            $result = [
                'success' => true,
                'domain' => $domain,
            ];

            // Status
            preg_match_all('/<domain:status s="([^"]+)"/', $responseXml, $statusMatches);
            if ($statusMatches[1] !== []) {
                $result['status'] = $statusMatches[1];
            }

            // Registrant
            preg_match('/<domain:registrant>([^<]+)<\/domain:registrant>/', $responseXml, $registrantMatch);
            if (isset($registrantMatch[1])) {
                $result['registrant'] = $registrantMatch[1];
            }

            // Created date
            preg_match('/<domain:crDate>([^<]+)<\/domain:crDate>/', $responseXml, $createdMatch);
            if (isset($createdMatch[1])) {
                $result['created_date'] = $createdMatch[1];
            }

            // Expiry date
            preg_match('/<domain:exDate>([^<]+)<\/domain:exDate>/', $responseXml, $expiryMatch);
            if (isset($expiryMatch[1])) {
                $result['expiry_date'] = $expiryMatch[1];
            }

            return $result;
        } catch (Exception $exception) {
            Log::error('Failed to get domain info', [
                'domain' => $domain,
                'error' => $exception->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Service error: '.$exception->getMessage(),
            ];
        }
    }

    /**
     * Get common TLDs for domain suggestions
     */
    private function getCommonTlds(): array
    {
        if ($this->commonTlds === []) {
            $this->commonTlds = DomainPrice::query()->where('status', 'active')
                ->whereIn('type', [DomainType::Local, DomainType::International])
                ->latest()
                ->limit(20) // Limit to top 20 most recent TLDs
                ->pluck('tld')
                ->map(fn ($tld): string => mb_ltrim($tld, '.'))
                ->all();
        }

        return $this->commonTlds;
    }

    /**
     * Initialize the EPP client with retries
     *
     * @throws Exception
     */
    private function initializeClient(): void
    {
        $config = [
            'host' => $this->config['host'],
            'port' => (int) $this->config['port'],
            'username' => $this->config['username'],
            'password' => $this->config['password'],
            'ssl' => (bool) $this->config['ssl'],
            'local_cert' => $this->config['certificate'],
            'verify_peer' => false,
            'verify_peer_name' => false,
            'verify_host' => false,
            'debug' => (bool) ($this->config['debug'] ?? false),
            'timeout' => 30, // Add timeout
        ];

        $attempts = 0;
        $lastException = null;

        while ($attempts < $this->maxRetries) {
            try {
                $this->client = new EPPClient($config);

                return;
            } catch (Exception $e) {
                $lastException = $e;
                $attempts++;
                Log::warning(sprintf('EPP Client initialization attempt %d failed: ', $attempts).$e->getMessage());

                if ($attempts < $this->maxRetries) {
                    Sleep::sleep($this->retryDelay);
                }
            }
        }

        Log::error('EPP Client initialization failed after '.$this->maxRetries.' attempts');
        throw $lastException;
    }

    /**
     * Check if client is connected and try to reconnect if not
     *
     * @throws Exception
     */
    private function ensureConnection(): void
    {
        throw_unless($this->client instanceof EPPClient, Exception::class, 'EPP client not initialized');

        try {
            if (! $this->connected) {
                $greeting = $this->connectWithRetry();
                Log::info('EPP connection established', [
                    'greeting' => $greeting,
                    'host' => $this->config['host'],
                ]);
            }

            // Test connection with a simple check domain command
            try {
                $frame = new CheckDomain;
                $frame->addDomain('test.rw'); // Use a valid test domain
                $response = $this->client->request($frame);

                if (! ($response instanceof Response)) {
                    $this->connected = false;
                    throw new Exception('EPP connection test failed - invalid response');
                }

                $result = $response->results()[0];

                Log::debug('EPP connection test successful', [
                    'code' => $result->code(),
                    'message' => $result->message(),
                ]);
            } catch (Exception $e) {
                $this->connected = false;
                throw new Exception('EPP connection test failed: '.$e->getMessage(), $e->getCode(), $e);
            }
        } catch (Exception $exception) {
            $this->connected = false;
            Log::error('EPP connection error: '.$exception->getMessage(), [
                'host' => $this->config['host'],
                'trace' => $exception->getTraceAsString(),
            ]);
            throw new Exception('Failed to establish EPP connection: '.$exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * Extract domain name from EPP response item
     */
    private function extractDomainValue(array $item, string $key): string
    {
        // Try multiple possible paths for the domain name
        $namePaths = [
            $key.'._text',
            $key,
            '_text',
        ];

        foreach ($namePaths as $path) {
            $value = $this->getNestedValue($item, $path);
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        // If direct path doesn't work, check if the key itself is the domain name
        if (is_string($item[$key] ?? null)) {
            return $item[$key];
        }

        Log::warning('Unable to extract domain name from EPP response item', [
            'item' => $item,
            'key' => $key,
        ]);

        return '';
    }

    /**
     * Extract availability status from EPP response item
     */
    private function extractAvailabilityValue(array $item): bool
    {
        // Log the item structure for debugging
        Log::debug('Extracting availability value from item:', $item);

        // Check for availability in multiple possible locations
        $availabilityPaths = [
            'name.@attributes.avail',
            '@attributes.avail',
            'name.@name.avail',
            '@name.avail',
            'avail',
        ];

        foreach ($availabilityPaths as $path) {
            $value = $this->getNestedValue($item, $path);
            if ($value !== null) {
                Log::debug(sprintf('Found availability value at path %s:', $path), ['value' => $value]);

                // Check for both string '1' and boolean true
                return in_array($value, ['1', 1, true], true);
            }
        }

        Log::warning('No availability value found in EPP response item:', $item);

        return false;
    }

    /**
     * Extract reason from EPP response item
     */
    private function extractReasonValue(array $item): ?string
    {
        return $item['reason']['_text'] ?? $item['reason'] ?? null;
    }

    /**
     * Extract base name from a domain string (e.g., "example.com" -> "example")
     */
    private function extractBaseName(string $domain): string
    {
        return implode('.', array_slice(explode('.', $domain), 0, -1));
    }

    /**
     * Extract TLD from a domain string (e.g., "example.com" -> "com")
     */
    private function extractTld(string $domain): string
    {
        $parts = explode('.', $domain);

        return end($parts) ?: '';
    }

    /**
     * Clean TLD by removing leading dot and ensuring it's valid
     */
    private function cleanTld(string $tld): string
    {
        return mb_trim(mb_ltrim($tld, '.'), '.');
    }

    /**
     * Generate domain suggestions based on a base name
     */
    private function generateDomainSuggestions(string $baseName): array
    {
        $suggestions = [];

        foreach ($this->suggestionPatterns['prefix'] as $prefix) {
            foreach ($this->suggestionPatterns['suffix'] as $suffix) {
                foreach ($this->suggestionPatterns['separators'] as $separator) {
                    $suggestion = $baseName.$separator.$prefix.$suffix;
                    if (mb_strlen($suggestion) <= 63) { // EPP domain length limit
                        $suggestions[] = $suggestion;
                    }
                }
            }
        }

        return array_unique($suggestions);
    }

    /**
     * Determine the type of suggestion (e.g., "prefix", "suffix", "separator")
     */
    private function getSuggestionType(string $suggestedDomain, string $baseName): string
    {
        $baseLength = mb_strlen($baseName);
        $suggestedLength = mb_strlen($suggestedDomain);

        if ($suggestedLength > $baseLength) {
            return 'prefix';
        }

        if ($suggestedLength < $baseLength) {
            return 'suffix';
        }

        return 'separator';
    }

    /**
     * Helper method to get nested array values using dot notation
     */
    private function getNestedValue(array $array, string $path)
    {
        $keys = explode('.', $path);
        $value = $array;

        foreach ($keys as $key) {
            if (! is_array($value) || ! isset($value[$key])) {
                return null;
            }

            $value = $value[$key];
        }

        return $value;
    }
}
