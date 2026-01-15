<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateBillingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string,array|string>
     */
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'max:255'],
            'address_line_one' => ['required', 'string', 'max:255'],
            'address_line_two' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:255'],
            'country_code' => ['required', 'string', 'max:255'],

        ];
    }

    public function messages(): array
    {
        return [
            'full_name.required' => 'Full name is required',
            'email.required' => 'Email is required',
            'email.email' => 'Email is invalid',
            'email.max' => 'Email is too long',
            'company.required' => 'Company is required',
            'company.max' => 'Company is too long',
            'phone_number.required' => 'Phone number is required',
            'phone_number.max' => 'Phone number is too long',
            'address_line_one.required' => 'Address is required',
            'address_line_one.max' => 'Address line one is too long',
            'address_line_two.required' => 'Address line two is required',
            'address_line_two.max' => 'Address line two is too long',
            'city.required' => 'City is required',
            'city.max' => 'City is too long',
            'state.required' => 'State is required',
            'state.max' => 'State is too long',
            'postal_code.required' => 'Postcode is required',
            'postal_code.max' => 'Postcode is too long',
            'country_code.required' => 'Country is required',
            'country_code.max' => 'Country is too long',

        ];
    }
}
