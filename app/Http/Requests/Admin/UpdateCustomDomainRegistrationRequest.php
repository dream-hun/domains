<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\DomainStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

final class UpdateCustomDomainRegistrationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('domain_edit');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'owner_id' => ['required', 'integer', 'exists:users,id'],
            'years' => ['required', 'integer', 'min:1', 'max:10'],
            'status' => ['required', 'string', Rule::in(array_column(DomainStatus::cases(), 'value'))],
            'auto_renew' => ['sometimes', 'boolean'],
            'registered_at' => ['required', 'date'],
            'expires_at' => ['required', 'date', 'after:registered_at'],
            'custom_price' => ['nullable', 'numeric', 'min:0'],
            'custom_price_currency' => [
                'nullable',
                'string',
                'size:3',
                Rule::exists('currencies', 'code'),
                'required_with:custom_price',
            ],
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
            'owner_id.required' => 'The domain owner is required.',
            'owner_id.exists' => 'The selected user does not exist.',
            'years.required' => 'The registration period is required.',
            'years.min' => 'Minimum registration period is 1 year.',
            'years.max' => 'Maximum registration period is 10 years.',
            'status.required' => 'The domain status is required.',
            'status.in' => 'The selected status is invalid.',
            'registered_at.required' => 'The registration date is required.',
            'registered_at.date' => 'The registration date must be a valid date.',
            'expires_at.required' => 'The expiry date is required.',
            'expires_at.date' => 'The expiry date must be a valid date.',
            'expires_at.after' => 'The expiry date must be after the registration date.',
            'custom_price.numeric' => 'The custom price must be a number.',
            'custom_price.min' => 'The custom price must be at least 0.',
            'custom_price_currency.required_with' => 'Currency is required when custom price is provided.',
            'custom_price_currency.size' => 'The currency code must be exactly 3 characters.',
            'custom_price_currency.exists' => 'The selected currency does not exist.',
            'custom_price_notes.max' => 'The custom price notes may not be greater than 1000 characters.',
        ];
    }
}
