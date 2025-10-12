<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CheckoutRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'payment_method' => 'required|string|in:stripe,paypal',
            'coupon_code' => 'nullable|string|max:50',
            'discount' => 'nullable|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'billing_name' => 'required|string|max:255',
            'billing_email' => 'required|email|max:255',
            'billing_address' => 'nullable|string|max:255',
            'billing_city' => 'nullable|string|max:100',
            'billing_country' => 'nullable|string|max:100',
            'billing_postal_code' => 'nullable|string|max:20',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'payment_method.required' => 'Please select a payment method.',
            'payment_method.in' => 'The selected payment method is not supported.',
            'billing_name.required' => 'Billing name is required.',
            'billing_email.required' => 'Billing email is required.',
            'billing_email.email' => 'Please provide a valid email address.',
            'total.required' => 'Order total is required.',
            'total.min' => 'Order total must be greater than zero.',
        ];
    }

    /**
     * Get custom attribute names for validation errors.
     */
    public function attributes(): array
    {
        return [
            'billing_name' => 'billing name',
            'billing_email' => 'billing email',
            'billing_address' => 'billing address',
            'billing_city' => 'billing city',
            'billing_country' => 'billing country',
            'billing_postal_code' => 'postal code',
        ];
    }
}
