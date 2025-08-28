<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Exception;
use Illuminate\Http\JsonResponse;

final class ContactController extends Controller
{
    /**
     * Get contact details.
     */
    public function details(string $id): JsonResponse
    {
        try {
            $contact = Contact::findOrFail($id);

            return response()->json([
                'success' => true,
                'contact' => [
                    'id' => $contact->id,
                    'first_name' => $contact->first_name,
                    'last_name' => $contact->last_name,
                    'full_name' => $contact->full_name,
                    'title' => $contact->title,
                    'organization' => $contact->organization,
                    'address_one' => $contact->address_one,
                    'address_two' => $contact->address_two,
                    'city' => $contact->city,
                    'state_province' => $contact->state_province,
                    'postal_code' => $contact->postal_code,
                    'country_code' => $contact->country_code,
                    'phone' => $contact->phone,
                    'fax_number' => $contact->fax_number,
                    'email' => $contact->email,
                ],
            ]);
        } catch (Exception) {
            return response()->json([
                'success' => false,
                'message' => 'Contact not found',
            ], 404);
        }
    }
}
