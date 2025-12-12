<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\Hosting\BillingCycle;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AddSubscriptionRenewalToCartRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $validBillingCycles = array_map(
            static fn (BillingCycle $cycle): string => $cycle->value,
            BillingCycle::cases()
        );

        return [
            'billing_cycle' => [
                'required',
                'string',
                Rule::in($validBillingCycles),
            ],
            'quantity' => [
                'nullable',
                'integer',
                'min:1',
                'max:120',
            ],
        ];
    }

    /**
     * Get custom error messages for validation rules
     */
    public function messages(): array
    {
        return [
            'billing_cycle.required' => 'Please select a billing cycle for renewal.',
            'billing_cycle.in' => 'The selected billing cycle is invalid.',
            'quantity.integer' => 'Quantity must be a valid number.',
            'quantity.min' => 'Quantity must be at least 1 month.',
            'quantity.max' => 'Quantity cannot exceed 120 months (10 years).',
        ];
    }
}
