<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\DomainContact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class UserContactController extends Controller
{
    /**
     * Get all contacts for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $contacts = Contact::where('user_id', auth()->id())
            ->select('id', 'uuid', 'contact_id', 'name', 'organization', 'email')
            ->get();

        // Get the domain contacts to determine the contact type
        $contacts->each(function ($contact): void {
            // Get the domain contact types for this contact
            $domainContacts = DomainContact::where('contact_id', $contact->id)
                ->distinct((array) 'type')
                ->pluck('type')
                ->toArray();

            // Set the contact type based on domain contacts, or default to null
            $contact->contact_type = empty($domainContacts) ? null : $domainContacts[0];
        });

        return response()->json([
            'success' => true,
            'contacts' => $contacts,
        ]);
    }

    /**
     * Get full details for a specific contact.
     */
    public function show(int $id): JsonResponse
    {
        // Find the contact and ensure it belongs to the authenticated user
        $contact = Contact::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (! $contact) {
            return response()->json([
                'success' => false,
                'message' => 'Contact not found or does not belong to you',
            ], 404);
        }

        // Get the domain contact type for this contact
        $domainContact = DomainContact::where('contact_id', $contact->id)
            ->first();

        if ($domainContact) {
            $contact->contact_type = $domainContact->type;
        }

        return response()->json([
            'success' => true,
            'contact' => $contact,
        ]);
    }
}
