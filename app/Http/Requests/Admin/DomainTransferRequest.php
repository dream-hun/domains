<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class DomainTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('domain_transfer');
    }

    public function rules(): array
    {
        return [
            'auth_code' => ['required', 'string', 'min:8', 'max:255'],
            'registrant_contact_id' => ['required', 'exists:contacts,id'],
            'admin_contact_id' => ['nullable', 'exists:contacts,id'],
            'tech_contact_id' => ['nullable', 'exists:contacts,id'],
            'billing_contact_id' => ['nullable', 'exists:contacts,id'],
            'nameservers' => ['nullable', 'array', 'min:2', 'max:4'],
            'nameservers.*' => [
                'nullable',
                'string',
                'regex:/^[a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?)*$/',
                'max:255',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'auth_code.required' => 'Authorization code is required for domain transfer.',
            'auth_code.min' => 'Authorization code must be at least 8 characters long.',
            'registrant_contact_id.required' => 'Registrant contact is required.',
            'registrant_contact_id.exists' => 'Selected registrant contact does not exist.',
            'nameservers.min' => 'At least two nameservers are required.',
            'nameservers.max' => 'Maximum of four nameservers allowed.',
            'nameservers.*.regex' => 'Each nameserver must be a valid hostname.',
        ];
    }
}
