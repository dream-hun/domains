<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\Hosting\BillingCycle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

final class UpdateHostingPromotionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('hosting_promotion_edit');
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $billingCycles = array_map(static fn (BillingCycle $cycle): string => $cycle->value, BillingCycle::cases());

        return [
            'hosting_plan_id' => ['required', 'integer', 'exists:hosting_plans,id'],
            'billing_cycle' => ['required', 'string', Rule::in($billingCycles)],
            'discount_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
        ];
    }
}
