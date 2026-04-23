<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

final class CreateVpsSnapshotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('vps_snapshot_create');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:30', 'regex:/^[a-zA-Z0-9 \-]+$/'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'A snapshot name is required.',
            'name.max' => 'The snapshot name may not exceed 30 characters.',
            'name.regex' => 'The snapshot name may only contain letters, numbers, spaces, and dashes.',
            'description.max' => 'The description may not exceed 255 characters.',
        ];
    }
}
