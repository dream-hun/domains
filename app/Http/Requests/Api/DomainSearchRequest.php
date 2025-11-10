<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class DomainSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'query' => ['required', 'string', 'min:2', 'max:253'],
            'type' => ['nullable', 'string', Rule::in(['local', 'international', 'all'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'query' => $this->input('query', ''),
            'type' => $this->input('type', 'all'),
        ]);
    }
}
