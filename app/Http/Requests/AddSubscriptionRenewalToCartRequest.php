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
        ];
    }
}
