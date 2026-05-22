<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

final class PawaPayPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<int, string>|string>
     */
    public function rules(): array
    {
        return [
            'msisdn' => ['required', 'string', 'regex:/^\d{10,15}$/', 'max:20'],
            'billing_name' => ['required', 'string', 'max:255'],
            'billing_email' => ['required', 'email', 'max:255'],
            'billing_address' => ['nullable', 'string', 'max:255'],
            'billing_city' => ['nullable', 'string', 'max:255'],
            'billing_country' => ['nullable', 'string', 'max:255'],
            'billing_postal_code' => ['nullable', 'string', 'max:20'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'msisdn.required' => 'Phone number is required.',
            'msisdn.regex' => 'Phone number must be 10–15 digits (e.g. 250788123456).',
            'billing_name.required' => 'Billing name is required.',
            'billing_email.required' => 'Billing email is required.',
            'billing_email.email' => 'Please provide a valid email address.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('msisdn')) {
            $msisdn = preg_replace('/\D/', '', (string) $this->input('msisdn')) ?? '';

            if (str_starts_with($msisdn, '0')) {
                $msisdn = '250'.mb_substr($msisdn, 1);
            } elseif (! str_starts_with($msisdn, '250')) {
                $msisdn = '250'.$msisdn;
            }

            $this->merge(['msisdn' => $msisdn]);
        }
    }

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
