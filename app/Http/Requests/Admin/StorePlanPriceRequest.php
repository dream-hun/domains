<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\Hosting\BillingCycle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

final class StorePlanPriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('hosting_plan_price_create');
    }

    public function rules(): array
    {
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
        ];
    }
}
