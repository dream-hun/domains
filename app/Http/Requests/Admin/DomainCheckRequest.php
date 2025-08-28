<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class DomainCheckRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'domain_name' => ['required', 'string', 'regex:/^[a-zA-Z0-9\-\.]+$/'],
        ];
    }
}
