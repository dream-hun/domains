<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Gate;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateCurrencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('currency_edit');
    }

    public function rules(): array
    {
        $currencyId = $this->route('currency')->id;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'symbol' => ['sometimes', 'required', 'string', 'max:10'],
            'exchange_rate' => ['sometimes', 'required', 'numeric', 'min:0.000001'],
            'is_base' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'exchange_rate.min' => 'The exchange rate must be greater than zero.',
        ];
    }
}
