<?php

declare(strict_types=1);

namespace App\Actions\Domain;

use App\Models\Domain;

final class GetDomainContactStatsAction
{
    public function execute(Domain $domain): array
    {
        $contacts = $domain->contacts()->get();
        $contactTypes = $contacts->pluck('pivot.type')->unique()->values()->toArray();
        $requiredTypes = ['registrant', 'admin', 'technical', 'billing'];

        return [
            'total_contacts' => $contacts->count(),
            'contact_types' => $contactTypes,
            'has_all_required' => count(array_intersect($requiredTypes, $contactTypes)) === 4,
            'missing_types' => array_diff($requiredTypes, $contactTypes),
            'contacts' => $contacts->map(function ($contact): array {
                return [
                    'id' => $contact->id,
                    'email' => $contact->email,
                    'full_name' => $contact->full_name,
                    'type' => $contact->pivot->type,
                ];
            })->toArray(),
        ];
    }
}
