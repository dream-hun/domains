<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
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

        return [
            'msisdn' => ['required', 'string', 'max:20', 'min:10'],
            'pmethod' => ['required', 'string', 'in:momo,cc'],
            'billing_name' => ['required', 'string', 'max:255'],
            'billing_email' => ['required', 'email', 'max:255'],
            'billing_address' => ['nullable', 'string', 'max:255'],
            'billing_city' => ['nullable', 'string', 'max:255'],
            'billing_country' => ['nullable', 'string', 'max:255'],
            'billing_postal_code' => ['nullable', 'string', 'max:20'],
            'card_number' => ['required_if:pmethod,cc', 'nullable', 'string', 'max:20'],
            'expiry_date' => ['required_if:pmethod,cc', 'nullable', 'string', 'regex:/^(0[1-9]|1[0-2])\/?([0-9]{2})$/'],
            'cvv' => ['required_if:pmethod,cc', 'nullable', 'string', 'min:3', 'max:4'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'msisdn.required' => 'Phone number is required.',
            'msisdn.string' => 'Phone number must be a valid string.',
            'msisdn.min' => 'Phone number must be at least 10 characters.',
            'pmethod.required' => 'Payment method type is required.',
            'pmethod.in' => 'The selected payment method is not supported. Please choose Mobile Money or Card Payment.',
            'billing_name.required' => 'Billing name is required.',
            'billing_email.required' => 'Billing email is required.',
            'billing_email.email' => 'Please provide a valid email address.',
            'card_number.required_if' => 'Card number is required for card payments.',
            'expiry_date.required_if' => 'Expiry date is required for card payments.',
            'expiry_date.regex' => 'Expiry date must be in MM/YY format.',
            'cvv.required_if' => 'CVV is required for card payments.',
            'cvv.min' => 'CVV must be at least 3 digits.',
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

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, mixed>
     */
    protected function failedValidation(Validator $validator): void
    {
        if ($this->expectsJson()) {
            throw new ValidationException(
                $validator,
                response()->json([
                    'success' => false,
                    'error' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422)
            );
        }

        parent::failedValidation($validator);
    }
}
