<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateNameserversRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('domain_update');
    }

    public function rules(): array
    {
        return [
            'nameservers' => ['required', 'array', 'min:2', 'max:4'],
            'nameservers.*' => [
                'required',
                'string',
                'regex:/^[a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?)*$/',
                'max:255',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nameservers.required' => 'At least two nameservers are required.',
            'nameservers.min' => 'At least two nameservers are required.',
            'nameservers.max' => 'Maximum of four nameservers allowed.',
            'nameservers.*.required' => 'Each nameserver is required.',
            'nameservers.*.regex' => 'Each nameserver must be a valid hostname.',
            'nameservers.*.max' => 'Each nameserver cannot exceed 255 characters.',
        ];
    }
}
