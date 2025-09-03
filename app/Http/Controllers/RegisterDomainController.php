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
        $existingContacts = [];
        foreach ($contactTypes as $type) {
            $existingContacts[$type->value] = $userContacts->where('contact_type', $type)->values();
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
            $domainName = $validatedData['domain_name'];
            $useSingleContact = isset($validatedData['use_single_contact']) && $validatedData['use_single_contact'];
            $contacts = $this->processContactsFromForm($validatedData, $useSingleContact);
            $registrationYears = $this->getRegistrationYears($validatedData);
            $nameservers = $this->processNameserversFromForm($validatedData);

            // Use the action to handle domain registration
            $result = $this->registerDomainAction->handle(
                $domainName,
                $contacts,
                $registrationYears,
                $nameservers,
                $useSingleContact
            );

            if ($result['success']) {
                return redirect()
                    ->route('dashboard')
                    ->with('success', $result['message']);
            }

            return redirect()
                ->back()
                ->withInput()
                ->with('error', $result['message'] ?? 'Domain registration failed. Please try again.');

        } catch (Exception $e) {
            Log::error('Domain registration failed', [
                'domain' => $validatedData['domain_name'] ?? 'unknown',
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
     * Get registration years from cart items
     */
    private function getRegistrationYears(array $validatedData): int
    {
        if (isset($validatedData['registration_years'])) {
            return (int) $validatedData['registration_years'];
        }

        $cartItems = Cart::getContent();
        $firstItem = $cartItems->first();

        return $firstItem ? (int) $firstItem->quantity : 1;
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
