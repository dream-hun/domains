<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ContactType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class UpdateContactRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'contact_type' => ['nullable', new Enum(ContactType::class)],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'organization' => ['nullable', 'string', 'max:255'],
            'address_one' => ['required', 'string', 'max:255'],
            'address_two' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'state_province' => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:20'],
            'country_code' => ['required', 'string', 'size:2', 'exists:countries,iso_alpha2'],
            'phone' => ['required', 'string', 'max:20'],
            'phone_extension' => ['nullable', 'string', 'max:10'],
            'fax_number' => ['nullable', 'string', 'max:20'],
            'fax_ext' => ['nullable', 'string', 'max:10'],
            'email' => ['required', 'email', 'max:255'],
            'is_primary' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'contact_type.enum' => 'The selected contact type is invalid.',
            'first_name.required' => 'First name is required.',
            'last_name.required' => 'Last name is required.',
            'address_one.required' => 'Address line 1 is required.',
            'city.required' => 'City is required.',
            'state_province.required' => 'State/Province is required.',
            'postal_code.required' => 'Postal code is required.',
            'country_code.required' => 'Country code is required.',
            'country_code.size' => 'The country code must be a 2-letter ISO code.',
            'country_code.exists' => 'The selected country code is invalid.',
            'phone.required' => 'Phone number is required.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Please enter a valid email address.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->contact_type === '') {
            $this->merge(['contact_type' => null]);
        }
    }
}
