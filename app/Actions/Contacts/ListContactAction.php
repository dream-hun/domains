<?php

declare(strict_types=1);

namespace App\Actions\Contacts;

use App\Models\Contact;
use Illuminate\Pagination\LengthAwarePaginator;

final class ListContactAction
{
    public function handle(int $perPage = 10): LengthAwarePaginator
    {
        return Contact::query()
            ->select(['id', 'uuid', 'first_name', 'last_name', 'email', 'phone', 'address_one', 'city', 'country_code', 'organization', 'is_primary', 'created_at'])
            ->orderBy('is_primary', 'desc')
            ->latest()->paginate($perPage);
    }
}
