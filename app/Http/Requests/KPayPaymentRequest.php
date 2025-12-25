<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class KPayPaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        $pmethod = $this->input('pmethod');

        $rules = [
            'msisdn' => ['required', 'string', 'max:20', 'min:10'],
            'pmethod' => ['required', 'string', 'in:momo,cc,spenn'],
            'billing_name' => ['required', 'string', 'max:255'],
            'billing_email' => ['required', 'email', 'max:255'],
            'billing_address' => ['nullable', 'string', 'max:255'],
            'billing_city' => ['nullable', 'string', 'max:255'],
            'billing_country' => ['nullable', 'string', 'max:255'],
            'billing_postal_code' => ['nullable', 'string', 'max:20'],
        ];

        // Card fields are required when payment method is 'cc'
        if ($pmethod === 'cc') {
            $rules['card_number'] = ['required', 'string', 'regex:/^[\d\s]{13,19}$/'];
            $rules['card_expiry'] = ['required', 'string', 'regex:/^\d{2}\/\d{2}$/'];
            $rules['card_cvv'] = ['required', 'string', 'regex:/^\d{3,4}$/'];
            $rules['cardholder_name'] = ['required', 'string', 'max:255'];
        }

        return $rules;
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'msisdn.required' => 'Phone number is required for Mobile Money payment.',
            'msisdn.string' => 'Phone number must be a valid string.',
            'msisdn.min' => 'Phone number must be at least 10 characters.',
            'pmethod.required' => 'Payment method type is required.',
            'pmethod.in' => 'The selected payment method type is not supported.',
            'billing_name.required' => 'Billing name is required.',
            'billing_email.required' => 'Billing email is required.',
            'billing_email.email' => 'Please provide a valid email address.',
            'card_number.required' => 'Card number is required for card payment.',
            'card_number.regex' => 'Please enter a valid card number.',
            'card_expiry.required' => 'Card expiry date is required.',
            'card_expiry.regex' => 'Please enter a valid expiry date (MM/YY).',
            'card_cvv.required' => 'CVV is required.',
            'card_cvv.regex' => 'Please enter a valid CVV (3 or 4 digits).',
            'cardholder_name.required' => 'Cardholder name is required.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Trim MSISDN to remove whitespace
        if ($this->has('msisdn')) {
            $this->merge([
                'msisdn' => mb_trim((string) $this->input('msisdn')),
            ]);
        }
    }
}
