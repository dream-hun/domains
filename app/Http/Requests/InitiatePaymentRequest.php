<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class InitiatePaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'msisdn' => ['required', 'string', 'regex:/^\+?[0-9]{9,15}$/'],
            'email' => ['required', 'email'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_number' => ['required', 'string', 'max:255'],
            'payment_method' => ['required', 'string', 'in:momo,cc,spenn'],
        ];

    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'msisdn.required' => 'Phone number is required.',
            'msisdn.regex' => 'Please enter a valid phone number (e.g., +250788123456 or 0788123456).',
            'email.required' => 'Email address is required.',
            'email.email' => 'Please enter a valid email address.',
            'customer_name.required' => 'Customer name is required.',
            'customer_number.required' => 'Customer number is required.',
            'payment_method.required' => 'Please select a payment method.',
            'payment_method.in' => 'The selected payment method is invalid.',
        ];
    }
}
