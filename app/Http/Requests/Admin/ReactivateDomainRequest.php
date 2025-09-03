<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class ReactivateDomainRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('domain_edit');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'domain' => ['required', 'string', 'max:255', 'exists:domains,name'],
        ];
    }

    public function messages(): array
    {
        return [
            'domain.required' => 'Domain name is required.',
            'domain.string' => 'Domain name must be a valid string.',
            'domain.max' => 'Domain name cannot exceed 255 characters.',
            'domain.exists' => 'The specified domain does not exist in your account.',
        ];
    }
}
