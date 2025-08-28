<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

final class UpdateContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('contact_edit');
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'organization' => ['nullable', 'string', 'max:255'],
            'address_one' => ['required', 'string', 'max:255'],
            'address_two' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'state_province' => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:20'],
            'country_code' => ['required', 'string', 'size:2'],
            'phone' => ['required', 'string', 'max:20'],
            'phone_extension' => ['nullable', 'string', 'max:10'],
            'fax_number' => ['nullable', 'string', 'max:20'],
            'fax_ext' => ['nullable', 'string', 'max:10'],
            'email' => ['required', 'email', 'max:255'],
            'contact_type' => ['nullable', 'string', Rule::in(['admin', 'billing', 'registrant', 'technical'])],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'The phone number must be in international format (e.g., +27.844784784)',
            'fax_number.regex' => 'The fax number must be in international format (e.g., +27.844784784)',
            'country_code.size' => 'The country code must be a 2-letter ISO code',
        ];
    }
}
