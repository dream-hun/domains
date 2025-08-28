<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Contacts\ListContactAction;
use App\Actions\CreateContactAction;
use App\Actions\DeleteContactAction;
use App\Actions\UpdateContactAction;
use App\Enums\ContactType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContactRequest;
use App\Http\Requests\UpdateContactRequest;
use App\Models\Contact;
use App\Models\Country;
use App\Services\Domain\EppDomainService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Log;
use Symfony\Component\HttpFoundation\Response;

final class ContactController extends Controller
{
    public function __construct(
        private readonly CreateContactAction $createContactAction,
        private readonly UpdateContactAction $updateContactAction,
        private readonly DeleteContactAction $deleteContactAction
    ) {}

    public function index(ListContactAction $action): View
    {
        abort_if(Gate::denies('contact_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $contacts = $action->handle();

        return view('admin.contacts.index', [
            'contacts' => $contacts,
            'contactTypes' => ContactType::cases(),
        ]);
    }

    public function create(): View
    {
        $countries = Country::orderBy('name')->get();
        $contactTypes = ContactType::cases();

        return view('admin.contacts.create', [
            'countries' => $countries,
            'contactTypes' => $contactTypes,
        ]);
    }

    /**
     * Store newly created contact
     */
    public function store(StoreContactRequest $request): RedirectResponse
    {
        $result = $this->createContactAction->handle(
            Auth::user(),
            $request->validated()
        );

        if ($result['success']) {
            return redirect()
                ->route('admin.contacts.index')
                ->with('success', $result['message']);
        }

        return redirect()
            ->back()
            ->withInput()
            ->with('error', $result['message']);
    }

    /**
     * Display the specified contact
     */
    public function show(Contact $contact): View
    {
        abort_if(Gate::denies('contact_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $contact->load(['user', 'domains']);

        // Initialize variables for EPP comparison
        $eppContact = null;
        $differences = [];
        $hasDifferences = false;

        // Try to fetch EPP data if contact has a contact_id
        if ($contact->contact_id) {
            try {
                $eppService = app(EppDomainService::class);
                $eppResult = $eppService->infoContact($contact->contact_id);

                if ($eppResult && isset($eppResult['contact'])) {
                    $eppContact = $eppResult['contact'];

                    // Compare local and EPP data
                    $differences = $this->compareContactData($contact, $eppContact);
                    $hasDifferences = $differences !== [];
                }
            } catch (Exception $e) {
                // Log the error but don't fail the page
                Log::warning('Failed to fetch EPP contact data', [
                    'contact_id' => $contact->contact_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return view('admin.contacts.show', [
            'contact' => $contact,
            'epp_contact' => $eppContact,
            'differences' => $differences,
            'has_differences' => $hasDifferences,
        ]);
    }

    /**
     * Show the form for editing the specified contact
     */
    public function edit(Contact $contact): View
    {
        abort_if(Gate::denies('contact_edit', $contact), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $countries = Country::all();
        $contactTypes = ContactType::cases();

        return view('admin.contacts.edit', [
            'contact' => $contact,
            'countries' => $countries,
            'contactTypes' => $contactTypes,
        ]);
    }

    /**
     * Update the specified contact
     */
    public function update(UpdateContactRequest $request, Contact $contact): RedirectResponse
    {

        $result = $this->updateContactAction->handle($contact, $request->validated());

        if ($result['success']) {
            return redirect()
                ->route('admin.contacts.show', $contact)
                ->with('success', $result['message']);
        }

        return redirect()
            ->back()
            ->withInput()
            ->with('error', $result['message']);
    }

    /**
     * Remove the specified contact
     */
    public function destroy(Contact $contact): RedirectResponse
    {
        abort_if(Gate::denies('contact_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $result = $this->deleteContactAction->handle($contact);

        if ($result['success']) {
            return redirect()
                ->route('admin.contacts.index')
                ->with('success', $result['message']);
        }

        return redirect()
            ->back()
            ->with('error', $result['message']);
    }

    /**
     * Compare local contact data with EPP registry data
     */
    private function compareContactData(Contact $contact, array $eppData): array
    {
        $differences = [];

        // Compare name
        $localName = $contact->first_name.' '.$contact->last_name;
        $eppName = $eppData['name'] ?? '';
        if (mb_trim($localName) !== mb_trim($eppName)) {
            $differences['name'] = true;
        }

        // Compare organization
        $localOrg = $contact->organization ?? '';
        $eppOrg = $eppData['organization'] ?? '';
        if (mb_trim($localOrg) !== mb_trim($eppOrg)) {
            $differences['organization'] = true;
        }

        // Compare email
        if ($contact->email !== ($eppData['email'] ?? '')) {
            $differences['email'] = true;
        }

        // Compare phone
        if ($contact->phone !== ($eppData['voice'] ?? '')) {
            $differences['voice'] = true;
        }

        // Compare address fields
        if ($contact->address_one !== ($eppData['streets'][0] ?? '')) {
            $differences['street1'] = true;
        }

        if ($contact->address_two !== ($eppData['streets'][1] ?? '')) {
            $differences['street2'] = true;
        }

        if ($contact->city !== ($eppData['city'] ?? '')) {
            $differences['city'] = true;
        }

        if ($contact->state_province !== ($eppData['province'] ?? '')) {
            $differences['province'] = true;
        }

        if ($contact->postal_code !== ($eppData['postal_code'] ?? '')) {
            $differences['postal_code'] = true;
        }

        if ($contact->country_code !== ($eppData['country_code'] ?? '')) {
            $differences['country_code'] = true;
        }

        return $differences;
    }
}
