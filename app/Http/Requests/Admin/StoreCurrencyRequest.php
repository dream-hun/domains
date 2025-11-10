<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

final class StoreCurrencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('currency_create');
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'size:3', 'uppercase', 'unique:currencies,code'],
            'name' => ['required', 'string', 'max:255'],
            'symbol' => ['required', 'string', 'max:10'],
            'exchange_rate' => ['required', 'numeric', 'min:0.000001'],
            'is_base' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.size' => 'The currency code must be exactly 3 characters.',
            'code.uppercase' => 'The currency code must be uppercase.',
            'exchange_rate.min' => 'The exchange rate must be greater than zero.',
        ];
    }
}
