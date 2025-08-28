<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

final class CreateContactRequest extends FormRequest
{
    public function authorize(): true
    {
        return Gate::allows('contact_create');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'organization' => ['nullable', 'string', 'max:255'],
            'street1' => ['required', 'string', 'max:255'],
            'street2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'province' => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:20'],
            'country_code' => ['required', 'string', 'size:2'],
            'voice' => ['required', 'string'],
            'fax' => ['nullable', 'string'],
            'fax_ext' => ['nullable', 'string', 'max:10'],
            'email' => ['required', 'email'],
            'type' => ['nullable', 'string', 'in:admin,billing,registrant,technical'],
        ];
    }

    public function messages(): array
    {
        return [
            'voice.regex' => 'The voice number must be in international format (e.g., +27.844784784)',
            'fax.regex' => 'The fax number must be in international format (e.g., +27.844784784)',
            'country_code.size' => 'The country code must be a 2-letter ISO code',
        ];
    }
}
