<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\Hosting\BillingCycle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

final class UpdatePlanPriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('hosting_plan_price_edit');
    }

    public function rules(): array
    {
        $hostingPlanPrice = $this->route('hosting_plan_price');
        $priceFields = ['regular_price', 'renewal_price'];
        $hasPriceChange = false;

        if ($hostingPlanPrice !== null) {
            foreach ($priceFields as $field) {
                $inputValue = (float) $this->input($field);
                $currentValue = (float) $hostingPlanPrice->{$field};

                if (abs($inputValue - $currentValue) > 0.001) {
                    $hasPriceChange = true;
                    break;
                }
            }
        }

        return [
            'hosting_category_id' => ['required', 'integer', 'exists:hosting_categories,id'],
            'hosting_plan_id' => [
                'required',
                'integer',
                Rule::exists('hosting_plans', 'id')->where(function ($query): void {
                    $query->where('category_id', $this->integer('hosting_category_id'));
                }),
            ],
            'billing_cycle' => ['required', 'string', Rule::in(BillingCycle::values())],
            'regular_price' => ['required', 'numeric', 'min:0'],
            'renewal_price' => ['required', 'numeric', 'min:0'],
            'currency_id' => ['required', 'integer', 'exists:currencies,id'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
            'is_current' => ['required', 'boolean'],
            'effective_date' => ['required', 'date'],
            'reason' => $hasPriceChange ? ['required', 'string', 'min:3', 'max:1000'] : ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'Please provide a reason for the price change.',
            'reason.min' => 'The reason must be at least :min characters.',
            'reason.max' => 'The reason must not exceed :max characters.',
        ];
    }
}
