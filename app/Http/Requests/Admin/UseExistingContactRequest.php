<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

final class UseExistingContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('contact_create');
    }

    public function rules(): array
    {
        return [
            'registrant_contact_id' => ['required', 'exists:contacts,id'],
            'admin_contact_id' => ['required', 'exists:contacts,id'],
            'tech_contact_id' => ['required', 'exists:contacts,id'],
            'use_existing_contacts' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'registrant_contact_id.required' => 'Please select a registrant contact.',
            'admin_contact_id.required' => 'Please select an admin contact.',
            'tech_contact_id.required' => 'Please select a technical contact.',
        ];
    }
}
