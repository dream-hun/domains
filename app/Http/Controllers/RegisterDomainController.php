<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\RegisterDomainAction;
use App\Enums\ContactType;
use App\Enums\DomainType;
use App\Http\Requests\RegisterDomainRequest;
use App\Models\Country;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Log;

final class RegisterDomainController extends Controller
{
    public function __construct(
        private readonly RegisterDomainAction $registerDomainAction
    ) {}

    public function index(): View
    {
        $cartItems = Cart::getContent();
        $domainName = $cartItems->first()?->name ?? '';
        $tld = mb_strrpos($domainName, '.') ? mb_substr($domainName, mb_strrpos($domainName, '.') + 1) : '';
        $domainType = mb_strtolower($tld) === 'rw' ? DomainType::Local : DomainType::International;

        $cartTotal = Cart::getTotal();
        $contactTypes = ContactType::cases();
        $userContacts = auth()->user()->contacts()->latest()->get();

        // Show all user contacts for every contact type dropdown
        $existingContacts = [];
        foreach ($contactTypes as $type) {
            $existingContacts[$type->value] = $userContacts;
        }

        $countries = Country::all();

        return view('domains.register', [
            'cartItems' => $cartItems,
            'cartTotal' => $cartTotal,
            'countries' => $countries,
            'contactTypes' => $contactTypes,
            'userContacts' => $userContacts,
            'existingContacts' => $existingContacts,
            'domainType' => $domainType,
        ]);
    }

    public function register(RegisterDomainRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validatedData = $request->validated();

        try {
            $cartItems = Cart::getContent();

            if ($cartItems->isEmpty()) {
                return redirect()
                    ->back()
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

            // Clear cart only after processing all domains
            Cart::clear();

            // Prepare response messages
            $messages = [];

            if ($successfulRegistrations !== []) {
                $successCount = count($successfulRegistrations);
                if ($successCount === 1) {
                    $messages[] = "Domain {$successfulRegistrations[0]} has been successfully registered!";
                } else {
                    $messages[] = "{$successCount} domains have been successfully registered: ".implode(', ', $successfulRegistrations);
                }
            }

            if ($failedRegistrations !== []) {
                $failureCount = count($failedRegistrations);
                if ($failureCount === 1) {
                    $messages[] = "Failed to register {$failedRegistrations[0]['domain']}: {$failedRegistrations[0]['message']}";
                } else {
                    $messages[] = "{$failureCount} domains failed to register. Please check your email for details or contact support.";
                }
            }

            // Determine redirect and message type
            if ($successfulRegistrations !== [] && $failedRegistrations === []) {
                // All successful
                return redirect()
                    ->route('dashboard')
                    ->with('success', implode(' ', $messages));
            }
            if ($successfulRegistrations !== [] && $failedRegistrations !== []) {
                // Partial success
                return redirect()
                    ->route('dashboard')
                    ->with('warning', implode(' ', $messages));
            }

            // All failed
            return redirect()
                ->back()
                ->withInput()
                ->with('error', implode(' ', $messages));

        } catch (Exception $e) {
            Log::error('Domain registration process failed', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
            ]);

            return redirect()
                ->back()
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
            // Use the same contact for all roles when single contact is selected
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
                if (! isset($processedContacts[$type])) {
                    throw new Exception("Missing contact for type: $type");
                }
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
}
