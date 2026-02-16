<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

final class StoreDomainPriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('domain_pricing_create');
    }

    public function rules(): array
    {
        return [
            'tld' => ['required', 'string', 'unique:domain_prices,tld'],
            'currency' => ['required', 'string', 'in:USD,RWF'],
            'register_price' => ['required', 'integer', 'min:0'],
            'renewal_price' => ['required', 'integer', 'min:0'],
            'transfer_price' => ['required', 'integer', 'min:0'],
            'redemption_price' => ['nullable', 'integer', 'min:0'],
            'min_years' => ['nullable', 'integer', 'min:1'],
            'max_years' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
            'description' => ['nullable', 'string'],
        ];
    }
}
