<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StripePaymentRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'billing_name' => 'required|string|max:255',
            'billing_email' => 'required|email|max:255',
            'billing_address' => 'nullable|string|max:255',
            'billing_city' => 'nullable|string|max:255',
            'billing_country' => 'nullable|string|max:255',
            'billing_postal_code' => 'nullable|string|max:20',
        ];
    }
}
