<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

final class ExtendVpsStorageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('vps_extend_storage');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'storage_gb' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'storage_gb.required' => 'Storage size is required.',
            'storage_gb.integer' => 'Storage size must be a whole number.',
            'storage_gb.min' => 'Storage size must be at least 1 GB.',
        ];
    }
}
