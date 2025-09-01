<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ToggleDomainLockRequest extends FormRequest
{
    public function authorize(): bool
    {
        // You may want to add authorization logic here
        return true;
    }

    public function rules(): array
    {
        return [
            'lock' => ['required', 'boolean'],
            'domain_id' => ['required', 'exists:domains,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'lock.required' => 'Lock status is required.',
            'lock.boolean' => 'Lock status must be true or false.',
            'domain_id.required' => 'Domain ID is required.',
            'domain_id.exists' => 'Domain not found.',
        ];
    }
}
