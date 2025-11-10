<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\Contact;

final class ContactHelper
{
    public function mapContacts(): void
    {
        Contact::query()->select('id', (array) 'contact_id', (array) 'first_name', (array) 'last_name', (array) 'email', (array) 'voice')
            ->latest()
            ->get();
    }
}
