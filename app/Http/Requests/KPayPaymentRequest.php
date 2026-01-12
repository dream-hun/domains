<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

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
        ];
    }

    /**
     * Get custom error messages for validation rules.
     * @return array
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
     * @param Validator $validator
     * @return void
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
