<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

final class StoreTldPricingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('tld_pricing_create');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'tld_id' => ['required', 'integer', 'exists:tld,id'],
            'currency_id' => ['required', 'integer', 'exists:currencies,id'],
            'register_price' => ['required', 'integer', 'min:0'],
            'renew_price' => ['required', 'integer', 'min:0'],
            'redemption_price' => ['required', 'integer', 'min:0'],
            'transfer_price' => ['required', 'integer', 'min:0'],
            'is_current' => ['required', 'boolean'],
            'effective_date' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
