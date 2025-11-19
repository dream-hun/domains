<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\RegisterDomainAction;
use App\Enums\ContactType;
use App\Enums\DomainType;
use App\Http\Requests\RegisterDomainRequest;
use App\Models\Country;
use App\Models\Currency;
use App\Services\CurrencyService;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

final class RegisterDomainController extends Controller
{
    public function __construct(
        private readonly RegisterDomainAction $registerDomainAction,
        private readonly CurrencyService $currencyService
    ) {}

    public function index(): View
    {
        $cartItems = Cart::getContent();
        $firstItem = $cartItems->first();
        $domainName = $firstItem !== null ? $firstItem->name : '';
        $tld = mb_strrpos((string) $domainName, '.') ? mb_substr((string) $domainName, mb_strrpos((string) $domainName, '.') + 1) : '';
        $domainType = mb_strtolower($tld) === 'rw' ? DomainType::Local : DomainType::International;

        $contactTypes = ContactType::cases();
        $userContacts = auth()->user()->contacts()->latest()->get();

        // Show all user contacts for every contact type dropdown
        $existingContacts = [];
        foreach ($contactTypes as $type) {
            $existingContacts[$type->value] = $userContacts;
        }

        $countries = Country::all();

        // Determine display currency (user preferred or type-based default)
        $userCurrency = $this->currencyService->getUserCurrency();
        $displayCurrency = mb_strtoupper($userCurrency instanceof Currency ? $userCurrency->code : $this->getDefaultCurrencyForDomainType($domainType));

        // Process cart items with currency conversion
        [$convertedItems, $cartTotalNumeric] = $this->processCartItemsWithCurrency($cartItems, $displayCurrency);
        $formattedCartTotal = $this->formatAmount($cartTotalNumeric, $displayCurrency);

        return view('domains.register', [
            'cartItems' => $cartItems,
            'countries' => $countries,
            'contactTypes' => $contactTypes,
            'userContacts' => $userContacts,
            'existingContacts' => $existingContacts,
            'domainType' => $domainType,
            'displayCurrency' => $displayCurrency,
            'convertedItems' => $convertedItems,
            'convertedCartTotal' => $formattedCartTotal,
            'convertedCartTotalNumeric' => $cartTotalNumeric,
        ]);
    }

    public function register(RegisterDomainRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validatedData = $request->validated();

        try {
            $cartItems = Cart::getContent();

            if ($cartItems->isEmpty()) {
                return back()
                    ->with('error', 'No domains found in cart to register.');
            }

            $useSingleContact = isset($validatedData['use_single_contact']) && $validatedData['use_single_contact'];
            $contacts = $this->processContactsFromForm($validatedData, $useSingleContact);
            $nameservers = $this->processNameserversFromForm($validatedData);

            $successfulRegistrations = [];
            $failedRegistrations = [];

            // Process each domain in the cart
            foreach ($cartItems as $cartItem) {
                $domainName = $cartItem->name;
                $registrationYears = (int) $cartItem->quantity;

                try {
                    // Use the action to handle domain registration
                    $result = $this->registerDomainAction->handle(
                        $domainName,
                        $contacts,
                        $registrationYears,
                        $nameservers,
                        $useSingleContact
                    );

                    if ($result['success']) {
                        $successfulRegistrations[] = $domainName;
                        Log::info('Domain registered successfully', [
                            'domain' => $domainName,
                            'user_id' => $user->id,
                        ]);
                    } else {
                        $failedRegistrations[] = [
                            'domain' => $domainName,
                            'message' => $result['message'] ?? 'Unknown error',
                        ];
                        Log::error('Domain registration failed', [
                            'domain' => $domainName,
                            'error' => $result['message'] ?? 'Unknown error',
                            'user_id' => $user->id,
                        ]);
                    }
                } catch (Exception $e) {
                    $failedRegistrations[] = [
                        'domain' => $domainName,
                        'message' => $e->getMessage(),
                    ];
                    Log::error('Domain registration exception', [
                        'domain' => $domainName,
                        'error' => $e->getMessage(),
                        'user_id' => $user->id,
                    ]);
                }
            }

            // Clear the cart only after processing all domains
            Cart::clear();

            // Prepare response messages
            $messages = [];

            if ($successfulRegistrations !== []) {
                $successCount = count($successfulRegistrations);
                if ($successCount === 1) {
                    $messages[] = sprintf('Domain %s has been successfully registered!', $successfulRegistrations[0]);
                } else {
                    $messages[] = $successCount.' domains have been successfully registered: '.implode(', ', $successfulRegistrations);
                }
            }

            if ($failedRegistrations !== []) {
                $failureCount = count($failedRegistrations);
                if ($failureCount === 1) {
                    $messages[] = sprintf('Failed to register %s: %s', $failedRegistrations[0]['domain'], $failedRegistrations[0]['message']);
                } else {
                    $messages[] = $failureCount.' domains failed to register. Please check your email for details or contact support.';
                }
            }

            // Determine redirect and message type
            if ($successfulRegistrations !== [] && $failedRegistrations === []) {
                // All successful
                return to_route('dashboard')
                    ->with('success', implode(' ', $messages));
            }

            if ($successfulRegistrations !== [] && $failedRegistrations !== []) {
                // Partial success
                return to_route('dashboard')
                    ->with('warning', implode(' ', $messages));
            }

            // All failed
            return back()
                ->withInput()
                ->with('error', implode(' ', $messages));

        } catch (Exception $exception) {
            Log::error('Domain registration process failed', [
                'error' => $exception->getMessage(),
                'user_id' => $user->id,
            ]);

            return back()
                ->withInput()
                ->with('error', 'An error occurred during domain registration. Please contact support.');
        }
    }

    /**
     * Process contacts from the simplified form structure
     *
     * @throws Exception
     */
    private function processContactsFromForm(array $validatedData, bool $useSingleContact): array
    {
        $processedContacts = [];
        $contactFields = [
            'registrant' => 'registrant_contact_id',
            'admin' => 'admin_contact_id',
            'technical' => 'tech_contact_id',
            'billing' => 'billing_contact_id',
        ];

        if ($useSingleContact && isset($validatedData['registrant_contact_id'])) {
            // Use the same contact for all roles when a single contact is selected
            $registrantContactId = $validatedData['registrant_contact_id'];
            foreach (array_keys($contactFields) as $type) {
                $processedContacts[$type] = $registrantContactId;
            }
        } else {
            // Process each contact type individually
            foreach ($contactFields as $type => $fieldName) {
                if (! empty($validatedData[$fieldName])) {
                    $processedContacts[$type] = $validatedData[$fieldName];
                }
            }

            // Ensure all required contact types are present
            $requiredTypes = ['registrant', 'admin', 'technical', 'billing'];
            foreach ($requiredTypes as $type) {
                throw_unless(isset($processedContacts[$type]), Exception::class, 'Missing contact for type: '.$type);
            }
        }

        return $processedContacts;
    }

    /**
     * Process nameservers from form structure
     */
    private function processNameserversFromForm(array $validatedData): array
    {
        if (isset($validatedData['disable_dns']) && $validatedData['disable_dns']) {
            return [];
        }

        $nameservers = array_filter($validatedData['nameservers'] ?? []);
        if ($nameservers === []) {
            // Return empty array to let RegisterDomainAction handle default nameservers
            return [];
        }

        return array_values(array_filter($nameservers, fn ($ns): bool => ! in_array(mb_trim($ns), ['', '0'], true)));
    }

    /**
     * Process cart items with currency conversion
     *
     * @return array{0: array<string, array>, 1: float} Tuple of [converted items array, cart total]
     */
    private function processCartItemsWithCurrency(mixed $cartItems, string $displayCurrency): array
    {
        $convertedItems = [];
        $cartTotalNumeric = 0.0;

        foreach ($cartItems as $cartItem) {
            $itemId = $cartItem->id ?? $cartItem->name;
            $itemCurrency = mb_strtoupper($cartItem->attributes->currency ?? 'USD');
            $unitPrice = (float) $cartItem->price;
            $quantity = (int) $cartItem->quantity;

            // Convert unit price to display currency
            $convertedUnitPrice = $this->convertAmount($unitPrice, $itemCurrency, $displayCurrency);
            $lineTotal = $convertedUnitPrice * $quantity;
            $cartTotalNumeric += $lineTotal;

            $convertedItems[$itemId] = [
                'id' => $itemId,
                'name' => $cartItem->name,
                'quantity' => $quantity,
                'unit_price' => $convertedUnitPrice,
                'line_total' => $lineTotal,
                'formatted_unit_price' => $this->formatAmount($convertedUnitPrice, $displayCurrency),
                'formatted_line_total' => $this->formatAmount($lineTotal, $displayCurrency),
            ];
        }

        return [$convertedItems, $cartTotalNumeric];
    }

    /**
     * Convert amount from one currency to another with fallback
     */
    private function convertAmount(float $amount, string $fromCurrency, string $toCurrency): float
    {
        if ($fromCurrency === $toCurrency) {
            return $amount;
        }

        try {
            return $this->currencyService->convert($amount, $fromCurrency, $toCurrency);
        } catch (Exception $exception) {
            Log::warning('Currency conversion failed', [
                'from' => $fromCurrency,
                'to' => $toCurrency,
                'amount' => $amount,
                'error' => $exception->getMessage(),
            ]);

            return $amount; // Fallback to original amount
        }
    }

    /**
     * Format amount in given currency with fallback
     */
    private function formatAmount(float $amount, string $currency): string
    {
        try {
            return $this->currencyService->format($amount, $currency);
        } catch (Exception $exception) {
            Log::warning('Currency formatting failed', [
                'currency' => $currency,
                'amount' => $amount,
                'error' => $exception->getMessage(),
            ]);

            return $currency.' '.number_format($amount, 2);
        }
    }

    /**
     * Get default currency code for domain type
     */
    private function getDefaultCurrencyForDomainType(DomainType $domainType): string
    {
        return $domainType === DomainType::Local ? 'RWF' : 'USD';
    }
}
