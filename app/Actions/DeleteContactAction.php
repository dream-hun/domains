<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Contact;
use Exception;
use Illuminate\Support\Facades\Log;

final class DeleteContactAction
{
    /**
     * Handle the deletion of a contact
     *
     * @param  Contact  $contact  The contact to delete
     * @return array{success: bool, message?: string}
     */
    public function handle(Contact $contact): array
    {
        try {
            $contactId = $contact->id;
            // $provider is not a property of Contact model, removing it or using appropriate attribute if it exists
            // Assuming this was intended to be logged but property doesn't exist on model
            $userId = $contact->user_id;

            // Delete the contact
            $contact->delete();

            Log::info('Contact deleted successfully', [
                'contact_id' => $contactId,
                'user_id' => $userId,
            ]);

            return [
                'success' => true,
                'message' => 'Contact deleted successfully.',
            ];
        } catch (Exception $exception) {
            Log::error('Contact deletion failed', [
                'contact_id' => $contact->id,
                'error' => $exception->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to delete contact: '.$exception->getMessage(),
            ];
        }
    }
}
