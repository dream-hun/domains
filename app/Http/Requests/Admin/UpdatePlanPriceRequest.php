<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

final class UpdatePlanPriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('hosting_plan_price_edit');
    }

    public function rules(): array
    {
        return [
            'hosting_plan_id' => ['required', 'exists:hosting_plans,id'],
            'billing_cycle' => ['required', 'string', 'in:monthly,quarterly,semi-annually,annually,biennially,triennially'],
            'regular_price' => ['required', 'integer', 'min:0'],
            'promotional_price' => ['nullable', 'integer', 'min:0'],
            'renewal_price' => ['required', 'integer', 'min:0'],
            'discount_percentage' => ['nullable', 'integer', 'min:0', 'max:100'],
            'promotional_start_date' => ['nullable', 'date'],
            'promotional_end_date' => ['nullable', 'date', 'after:promotional_start_date'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
        ];
    }
}
