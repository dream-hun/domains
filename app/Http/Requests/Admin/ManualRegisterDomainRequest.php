<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class ManualRegisterDomainRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('failed_registration_retry');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'domain_name' => ['required', 'string', 'max:255'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'years' => ['required', 'integer', 'min:1', 'max:10'],
            'registrant_contact_id' => ['required', 'integer', 'exists:contacts,id'],
            'admin_contact_id' => ['required', 'integer', 'exists:contacts,id'],
            'technical_contact_id' => ['required', 'integer', 'exists:contacts,id'],
            'billing_contact_id' => ['required', 'integer', 'exists:contacts,id'],
            'nameserver_1' => ['nullable', 'string', 'max:255'],
            'nameserver_2' => ['nullable', 'string', 'max:255'],
            'nameserver_3' => ['nullable', 'string', 'max:255'],
            'nameserver_4' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'domain_name.required' => 'Domain name is required.',
            'domain_name.string' => 'Domain name must be a string.',
            'domain_name.max' => 'Domain name cannot exceed 255 characters.',
            'user_id.required' => 'User is required.',
            'user_id.exists' => 'Selected user does not exist.',
            'years.required' => 'Registration period is required.',
            'years.integer' => 'Registration period must be a number.',
            'years.min' => 'Minimum registration period is 1 year.',
            'years.max' => 'Maximum registration period is 10 years.',
            'registrant_contact_id.required' => 'Registrant contact is required.',
            'registrant_contact_id.exists' => 'Selected registrant contact does not exist.',
            'admin_contact_id.required' => 'Admin contact is required.',
            'admin_contact_id.exists' => 'Selected admin contact does not exist.',
            'technical_contact_id.required' => 'Technical contact is required.',
            'technical_contact_id.exists' => 'Selected technical contact does not exist.',
            'billing_contact_id.required' => 'Billing contact is required.',
            'billing_contact_id.exists' => 'Selected billing contact does not exist.',
        ];
    }
}
