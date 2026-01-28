<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

final class CreateCustomSubscriptionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('subscription_create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'hosting_plan_id' => ['required', 'integer', 'exists:hosting_plans,id'],
            'custom_price' => ['nullable', 'numeric', 'min:0'],
            'custom_price_currency' => [
                'nullable',
                'string',
                'size:3',
                Rule::exists('currencies', 'code'),
                'required_with:custom_price',
            ],
            'billing_cycle' => ['required', 'string', Rule::in(['monthly', 'annually'])],
            'domain' => ['nullable', 'string', 'max:255'],
            'starts_at' => ['required', 'date'],
            'expires_at' => ['required', 'date', 'after:starts_at'],
            'auto_renew' => ['sometimes', 'boolean'],
            'custom_price_notes' => ['nullable', 'string', 'max:1000'],
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
            'user_id.required' => 'The user is required.',
            'user_id.exists' => 'The selected user does not exist.',
            'hosting_plan_id.required' => 'The hosting plan is required.',
            'hosting_plan_id.exists' => 'The selected hosting plan does not exist.',
            'custom_price.numeric' => 'The custom price must be a number.',
            'custom_price.min' => 'The custom price must be at least 0.',
            'custom_price_currency.required_with' => 'The currency is required when custom price is provided.',
            'custom_price_currency.exists' => 'The selected currency does not exist.',
            'custom_price_currency.size' => 'The currency code must be exactly 3 characters.',
            'billing_cycle.required' => 'The billing cycle is required.',
            'billing_cycle.in' => 'The billing cycle must be either monthly or annually.',
            'starts_at.required' => 'The start date is required.',
            'starts_at.date' => 'The start date must be a valid date.',
            'expires_at.required' => 'The expiry date is required.',
            'expires_at.date' => 'The expiry date must be a valid date.',
            'expires_at.after' => 'The expiry date must be after the start date.',
            'domain.max' => 'The domain name may not be greater than 255 characters.',
            'custom_price_notes.max' => 'The custom price notes may not be greater than 1000 characters.',
        ];
    }
}
