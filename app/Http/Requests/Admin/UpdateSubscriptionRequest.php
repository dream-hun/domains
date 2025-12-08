<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

final class UpdateSubscriptionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('subscription_edit');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(['active', 'expired', 'cancelled', 'suspended'])],
            'starts_at' => ['required', 'date'],
            'expires_at' => ['required', 'date', 'after:starts_at'],
            'next_renewal_at' => ['nullable', 'date'],
            'domain' => ['nullable', 'string', 'max:255'],
            'auto_renew' => ['sometimes', 'boolean'],
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
            'status.required' => 'The subscription status is required.',
            'status.in' => 'The selected status is invalid.',
            'starts_at.required' => 'The start date is required.',
            'starts_at.date' => 'The start date must be a valid date.',
            'expires_at.required' => 'The expiry date is required.',
            'expires_at.date' => 'The expiry date must be a valid date.',
            'expires_at.after' => 'The expiry date must be after the start date.',
            'next_renewal_at.date' => 'The next renewal date must be a valid date.',
            'domain.max' => 'The domain name may not be greater than 255 characters.',
        ];
    }
}
