<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateDomainContactsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('domain_edit');
    }

    public function rules(): array
    {
        // Check if this is the new form format or old format
        if ($this->has('contact_type')) {
            // New form format with individual fields
            return [
                'contact_type' => ['required', 'in:registrant,admin,technical,billing'],
                'first_name' => ['required', 'string', 'max:255'],
                'last_name' => ['required', 'string', 'max:255'],
                'organization' => ['nullable', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255'],
                'phone' => ['required', 'string', 'max:20'],
                'phone_country' => ['required', 'string', 'max:10'],
                'address_one' => ['required', 'string', 'max:255'],
                'address_two' => ['nullable', 'string', 'max:255'],
                'city' => ['required', 'string', 'max:255'],
                'state_province' => ['required', 'string', 'max:255'],
                'postal_code' => ['required', 'string', 'max:20'],
                'country_code' => ['required', 'string', 'size:2'],
                'fax_number' => ['nullable', 'string', 'max:20'],
            ];
        }

        // Old form format with contact IDs
        return [
            'registrant.contact_id' => ['sometimes', 'required', 'exists:contacts,id'],
            'admin.contact_id' => ['sometimes', 'required', 'exists:contacts,id'],
            'technical.contact_id' => ['sometimes', 'required', 'exists:contacts,id'],
            'billing.contact_id' => ['sometimes', 'required', 'exists:contacts,id'],
        ];

    }

    public function messages(): array
    {
        return [
            'registrant.contact_id.required' => 'A registrant contact is required',
            'admin.contact_id.required' => 'An administrative contact is required',
            'technical.contact_id.required' => 'A technical contact is required',
            'billing.contact_id.required' => 'A billing contact is required',
            '*.contact_id.exists' => 'The selected contact does not exist',
        ];
    }
}
