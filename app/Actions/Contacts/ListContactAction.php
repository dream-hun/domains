<?php

declare(strict_types=1);

namespace App\Actions\Contacts;

use App\Enums\ContactType;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

final class ListContactAction
{
    public function handle(?string $search = null, ?ContactType $contactType = null, int $perPage = 10): LengthAwarePaginator
    {
        $query = Contact::query()
            ->select([
                'id',
                'uuid',
                'first_name',
                'last_name',
                'email',
                'phone',
                'address_one',
                'city',
                'country_code',
                'organization',
                'is_primary',
                'created_at',
                'contact_type',
            ])
            ->orderBy('is_primary', 'desc')
            ->latest();

        if ($search !== null) {
            $like = '%'.$search.'%';
            $query->where(function (Builder $builder) use ($like): void {
                $builder->where('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhereRaw("TRIM(first_name || ' ' || last_name) LIKE ?", [$like])
                    ->orWhere('email', 'like', $like)
                    ->orWhere('organization', 'like', $like);
            });
        }

        if ($contactType instanceof ContactType) {
            $query->where('contact_type', $contactType->value);
        }

        return $query->paginate($perPage)->withQueryString();
    }
}
