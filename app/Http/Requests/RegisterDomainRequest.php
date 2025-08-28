<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class RegisterDomainRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, string|array>
     */
    public function rules(): array
    {
        $rules = [
            // Domain information
            'domain_name' => ['required', 'string', 'min:2', 'max:253'],
            'registration_years' => ['required', 'integer', 'min:1', 'max:10'],

            // Contact assignments - registrant is always required
            'registrant_contact_id' => ['required', 'exists:contacts,id'],

            // Single contact option
            'use_single_contact' => ['sometimes', 'boolean'],

            // Nameserver options
            'disable_dns' => ['sometimes', 'boolean'],
            'nameservers' => ['required_unless:disable_dns,1', 'array', 'min:2', 'max:4'],
            'nameservers.*' => ['nullable', 'string', 'max:255', 'regex:/^[a-zA-Z0-9][a-zA-Z0-9-]*[a-zA-Z0-9](\.[a-zA-Z0-9][a-zA-Z0-9-]*[a-zA-Z0-9])*$/'],

            // Terms and agreements
            'terms_accepted' => ['required', 'accepted'],
            'privacy_policy_accepted' => ['required', 'accepted'],
        ];

        // If not using single contact, require all contact types
        if (! $this->boolean('use_single_contact')) {
            $rules['admin_contact_id'] = ['required', 'exists:contacts,id'];
            $rules['tech_contact_id'] = ['required', 'exists:contacts,id'];
            $rules['billing_contact_id'] = ['required', 'exists:contacts,id'];
        } else {
            // If using single contact, these fields are optional (will be filled automatically)
            $rules['admin_contact_id'] = ['nullable', 'exists:contacts,id'];
            $rules['tech_contact_id'] = ['nullable', 'exists:contacts,id'];
            $rules['billing_contact_id'] = ['nullable', 'exists:contacts,id'];
        }

        return $rules;
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'domain_name.required' => 'Domain name is required.',
            'domain_name.min' => 'Domain name must be at least 2 characters.',
            'domain_name.max' => 'Domain name cannot exceed 253 characters.',

            'registration_years.required' => 'Registration period is required.',
            'registration_years.min' => 'Minimum registration period is 1 year.',
            'registration_years.max' => 'Maximum registration period is 10 years.',

            'registrant_contact_id.required' => 'Registrant contact is required.',
            'registrant_contact_id.exists' => 'Selected registrant contact is invalid.',
            'admin_contact_id.required' => 'Administrative contact is required.',
            'admin_contact_id.exists' => 'Selected administrative contact is invalid.',
            'tech_contact_id.required' => 'Technical contact is required.',
            'tech_contact_id.exists' => 'Selected technical contact is invalid.',
            'billing_contact_id.required' => 'Billing contact is required.',
            'billing_contact_id.exists' => 'Selected billing contact is invalid.',

            'nameservers.required_unless' => 'Nameservers are required unless DNS delegation is disabled.',
            'nameservers.min' => 'At least 2 nameservers are required.',
            'nameservers.max' => 'Maximum of 4 nameservers allowed.',
            'nameservers.*.regex' => 'Nameserver format is invalid. Use format like ns1.example.com',

            'terms_accepted.required' => 'You must accept the terms and conditions.',
            'terms_accepted.accepted' => 'You must accept the terms and conditions.',

            'privacy_policy_accepted.required' => 'You must accept the privacy policy.',
            'privacy_policy_accepted.accepted' => 'You must accept the privacy policy.',
        ];
    }

    /**
     * Get custom attributes for validation errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'domain_name' => 'domain name',
            'registration_years' => 'registration period',
            'registrant_contact_id' => 'registrant contact',
            'admin_contact_id' => 'administrative contact',
            'tech_contact_id' => 'technical contact',
            'billing_contact_id' => 'billing contact',
            'nameservers' => 'nameservers',
            'terms_accepted' => 'terms and conditions',
            'privacy_policy_accepted' => 'privacy policy',
        ];
    }
}
