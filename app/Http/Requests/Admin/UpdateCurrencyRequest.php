<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

final class UpdateCurrencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('currency_edit');
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'symbol' => ['sometimes', 'required', 'string', 'max:10'],
            'is_base' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
